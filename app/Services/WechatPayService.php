<?php

namespace App\Services;

use App\Wechatpay;
use WeChatPay\Builder;
use WeChatPay\Formatter;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;

class WechatPayService implements PayInterface
{
    private static $appId;
    private static $notify_url;
    private static $merchantId;
    private static $platformCertificateSerial = '5334E1BE2D1F8709A5CD5CED51BE86492D35F988';
    private static $apiKey = 'RAlTRo0JvEFdsgerhJznBNA3LZUiQIP1';

    /**
     * @param string $out_trade_no 订单号
     * @param integer $payment_code 支付方式 1：支付宝 2：微信
     * @param float $amount 金额
     * @param string $product_name 产品名称
     * @param integer $goods_id 商品Id
     * @return array
     */
    public static function order($out_trade_no, $payment_code, $amount, $product_name, $goods_id)
    {

        $amount = bcmul($amount, 100);
        $amount = intval($amount);

        try {
            //判断客户端
            if (isMobile()) {
                $resp = self::h5($product_name, $out_trade_no, $amount);

            } else {
                $resp = self::native($product_name, $out_trade_no, $amount);
            }
        } catch (\GuzzleHttp\Exception\ClientException | \GuzzleHttp\Exception\ServerException $e) {
            $resp = json_decode($e->getResponse()->getBody(), true);
            return [
                'code' => 0,
                'message' => $resp['message'] ?? '请求异常，请联系技术处理。'
            ];
        } catch (\InvalidArgumentException | \UnexpectedValueException $e) {
            return [
                'code' => 0,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'message' => $e->getMessage()
            ];
        }

        $resp = json_decode($resp, true);

        return [
            'code' => 1,
            'data' => [
                'qr_url' => is_array($resp) && array_key_exists('code_url', $resp) ? config('app.url') . '/pay_qr_code?qr_code=' . urlencode($resp['code_url']) : '',
                'h5' => is_array($resp) && array_key_exists('h5_url', $resp) ? $resp['h5_url'] : ''
            ],
        ];
    }

    /**
     * Native 支付
     * @param string $description 商品描述
     * @param string $out_trade_no 订单号
     * @param int $total 金额
     * @return \Psr\Http\Message\StreamInterface
     * @throws \Exception
     */
    private static function native($description, $out_trade_no, $total)
    {
        $resp = self::instance()->chain('v3/pay/transactions/native')->post([
            'json' => [
                'appid' => self::$appId,
                'mchid' => self::$merchantId,
                'description' => $description,
                'out_trade_no' => $out_trade_no,
                'time_expire' => date(DATE_RFC3339, time() + 1800),
                'notify_url' => self::$notify_url,
                'amount' => [
                    'total' => $total,
                    'currency' => 'CNY'
                ]
            ]
        ]);
        return $resp->getBody();

    }

    /**
     * H5 支付
     * @param string $description 商品描述
     * @param string $out_trade_no 订单号
     * @param int $total 金额
     * @return \Psr\Http\Message\StreamInterface
     * @throws \Exception
     */
    private static function h5($description, $out_trade_no, $total)
    {
        $resp = self::instance()->chain('v3/pay/transactions/h5')->post([
            'json' => [
                'appid' => self::$appId,
                'mchid' => self::$merchantId,
                'description' => $description,
                'out_trade_no' => $out_trade_no,
                'time_expire' => date(DATE_RFC3339, time() + 1800),
                'notify_url' => self::$notify_url,
                'amount' => [
                    'total' => $total,
                    'currency' => 'CNY'
                ],
                'scene_info' => [
                    'payer_client_ip' => request()->ip(),
                    'h5_info' => [
                        'type' => 'wap',
                        'app_name' => '元气开箱',
                        'app_url' => 'https://www.o2skins.com'
                    ]
                ]
            ]
        ]);
        return $resp->getBody();
    }

    /**
     * 异步通知验签内容解密
     * @return array|false
     */
    public static function verifyNotify()
    {
        $inWechatpaySignature = request()->header('Wechatpay-Signature');
        $inWechatpayTimestamp = request()->header('Wechatpay-Timestamp');
        $inWechatpaySerial = request()->header('Wechatpay-Serial');
        $inWechatpayNonce = request()->header('Wechatpay-Nonce');
        $userAgent = request()->header('User-Agent');
        $inBody = request()->getContent();

        if (empty($inWechatpaySignature) || empty($inWechatpayTimestamp) || empty($inWechatpaySerial) || empty($inWechatpayNonce) || empty($inBody) || $userAgent !== 'Mozilla/4.0') {
            return false;
        }

        $apiv3Key = self::$apiKey;
        $platformPublicKeyPath = config_path('cert') . '/wechatpay/platform/wechatpay_' . $inWechatpaySerial . '.pem';

        if (!is_file($platformPublicKeyPath)) {
            self::downloadCert();
        }

        $platformPublicKeyInstance = Rsa::from('file://' . $platformPublicKeyPath, Rsa::KEY_TYPE_PUBLIC);

        // 检查通知时间偏移量，允许5分钟之内的偏移
        $timeOffsetStatus = 86400 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
        $verifiedStatus = Rsa::verify(
        // 构造验签名串
            Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
            $inWechatpaySignature,
            $platformPublicKeyInstance
        );
        if ($timeOffsetStatus && $verifiedStatus) {
            // 转换通知的JSON文本消息为PHP Array数组
            $inBodyArray = (array)json_decode($inBody, true);
            // 使用PHP7的数据解构语法，从Array中解构并赋值变量
            ['resource' => [
                'ciphertext' => $ciphertext,
                'nonce' => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            // 加密文本消息解密
            $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
            // 把解密后的文本转换为PHP Array数组
            $inBodyResourceArray = (array)json_decode($inBodyResource, true);

            return $inBodyResourceArray;
        }
        return false;
    }

    /**
     * 微信支付实例
     * @return \WeChatPay\BuilderChainable
     */
    private static function instance($mchid = null)
    {
        if ($mchid === null) {
            $channelIds = Wechatpay::where('status', 1)->pluck('merchant_id')->toArray();
            if (empty($channelIds)) {
                throw new \Exception('没有检测到支付渠道，请检查系统配置。', -1);
            }
            $channelKey = array_rand($channelIds);
            $channelId = $channelIds[$channelKey];
        } else {
            $channelId = $mchid;
        }

        $config = Wechatpay::where('merchant_id', $channelId)->first();

        if (!$config) {
            throw new \Exception('配置获取失败，请检查系统配置。', -1);
        }

        self::$appId = $config->app_id;
        self::$notify_url = $config->notify_url;
        self::$merchantId = $config->merchant_id;

        $merchantId = self::$merchantId;
        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyFilePath = 'file://' . config_path('cert') . '/' . $config->private_key;
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        // 「商户API证书」的「证书序列号」
        $merchantCertificateSerial = $config->merchant_certificate_serial;
        // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名

        $platformCertificateFilePath = 'file://' . config_path('cert') . '/wechatpay/platform/wechatpay_' . self::$platformCertificateSerial . '.pem';
        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        // 从「微信支付平台证书」中获取「证书序列号」
        //$platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);

        return Builder::factory([
            'mchid' => $merchantId,
            'serial' => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => [
                self::$platformCertificateSerial => $platformPublicKeyInstance,
            ]
        ]);
    }

    /**
     * 证书下载
     * @throws \Exception
     */
    public static function downloadCert()
    {
        $resp = self::instance()->chain('v3/certificates')->get();
        $body = (string)$resp->getBody();
        $array = json_decode($body);
        foreach ($array->data as $cert) {
            $platformPublicKeyPath = config_path('cert') . '/wechatpay/platform/wechatpay_' . $cert->serial_no . '.pem';
            if (!is_file($platformPublicKeyPath)) {
                $encryptCertificate = $cert->encrypt_certificate;
                $certData = AesGcm::decrypt($encryptCertificate->ciphertext, self::$apiKey, $encryptCertificate->nonce, $encryptCertificate->associated_data);
                file_put_contents($platformPublicKeyPath, $certData);
            }
        }
    }
}
