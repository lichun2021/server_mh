<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UmiStrongPayService implements PayInterface
{
    /**
     * @var string 海智网关
     */
    public static $gateway = 'https://gateway.umistrong.com.cn/api/submit';

    /**
     * 海智支付下单
     * @param string $out_trade_no 订单号
     * @param integer $payment_code 支付方式 1：支付宝 2：微信
     * @param float $amount 金额
     * @param string $product_name 产品名称
     * @return array
     */
    public static function order($out_trade_no, $payment_code, $amount, $product_name, $goods_id = null)
    {
        if (!in_array($payment_code, [1, 2])) {
            return [
                'code' => 0,
                'message' => '不受支持的支付方式'
            ];
        }

        //重新定义支付渠道
        $payCode = [
            1 => 'alipayjsapi',
            2 => 'weixinjsapi'
        ];
        $payment_code = $payCode[$payment_code];
        $data = [
            'version' => '1.0',
            'customerid' => config('umistrongpay.customerid'),
            'totalfee' => $amount,
            'sdorderno' => $out_trade_no,
            'notifyurl' => config('umistrongpay.notifyurl'),
            'returnurl' => config('umistrongpay.returnurl'),

        ];
        $data['sign'] = self::getSign($data);
        $data['subject'] = $product_name;
        $data['paytype'] = $payment_code;
        $data['qrtype'] = 'string';
        $data['ip'] = request()->ip();
        //请求
        $response = Http::asForm()->post(self::$gateway, $data);
        $url = $response->body();
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return [
                'code' => 0,
                'message' => '系统异常，支付请求失败！'
            ];
        } else {
            $data = [
                'qr_url' => config('app.url') . '/pay_qr_code?qr_code=' . urlencode($url),
                'h5' => $url
            ];
            return [
                'code' => 1,
                'data' => $data,
            ];
        }
    }

    /**
     * 验证签名
     * @return bool
     */
    public static function verifySign()
    {
        $data = request()->post();
        $signArray = [
            'customerid' => $data['customerid'],
            'totalfee' => $data['totalfee'],
            'sdorderno' => $data['sdorderno'],
            'sdpayno' => $data['sdpayno'],
            'paytype' => $data['paytype']
        ];
        $sign = self::getSign($signArray);
        if ($sign === $data['sign']) {
            return true;
        }
        return false;
    }

    /**
     * 签名
     * @param array $paramArr
     * @return string
     */
    public static function getSign($paramArr)
    {
        $signStr = '';
        foreach ($paramArr as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        // 排好序的参数加上secret,进行md5
        $signStr .= 'apikey=' . config('umistrongpay.api_key');
        return md5($signStr);
    }
}
