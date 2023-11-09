<?php


namespace App\Services;

use App\Alipay;
use Illuminate\Support\Str;
use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Illuminate\Support\Facades\Cache;

class AlipayService implements PayInterface
{
    /**
     * @var array 支付通道
     */
    public static $channel;

    /**
     * @param string $out_trade_no 订单号
     * @param integer $payment_code 支付方式 1：支付宝 2：微信
     * @param float $amount 金额
     * @param string $product_name 产品名称
     * @param integer $goods_id 商品Id
     * @return mixed|string
     * @throws \Exception
     */
    public static function order($out_trade_no, $payment_code, $amount, $product_name, $goods_id)
    {
        //支付宝
        $channelIds = Alipay::where('status', 1)->pluck('id')->toArray();
        if (empty($channelIds)) {
            throw new \Exception('没有检测到支付渠道，请检查系统配置。',-1);
        }
        $channelKey = array_rand($channelIds);
        $channelId = $channelIds[$channelKey];

        self::getOptions($channelId);
        $result = Factory::payment()->wap()->pay($product_name, $out_trade_no, $amount, self::$channel['return_url'], self::$channel['return_url']);

        $k = 'ali_' . Str::random();
        $key = 'pay_' . $k;
        Cache::put($key, $result->body, 1800);
        $pay_url = config('app.url') . '/go?key=' . $k;
        $data = [
            'qr_url' => config('app.url') . '/pay_qr_code?qr_code=' . urlencode($pay_url),
            'h5' => $pay_url
        ];
        return [
            'code' => 1,
            'data' => $data,
        ];
    }

    /**
     * 验签
     * @return bool
     */
    public static function verifyNotify($data)
    {
        $channel = Alipay::select('id')->where('app_id', $data['app_id'])->first();
        if (!$channel) {
            return false;
        }
        self::getOptions($channel->id);
        return Factory::payment()->common()->verifyNotify($data);
    }

    /**
     * 配置
     * @return Config
     */
    private static function getOptions($channel_id)
    {
        self::$channel = Alipay::where('id', $channel_id)->first()->toArray();
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';
        $options->appId = self::$channel['app_id'];
        $options->merchantPrivateKey = self::$channel['private_key'];
        $options->alipayPublicKey = self::$channel['alipay_public_key'];
        $options->notifyUrl = self::$channel['notify_url'];

        $encryptKey = self::$channel['encrypt_key'];
        if (!empty($encryptKey)) {
            $options->encryptKey = $encryptKey;
        }
        Factory::setOptions($options);
    }
}
