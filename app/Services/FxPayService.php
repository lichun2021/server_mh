<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FxPayService implements PayInterface
{
    /**
     * 富信卡下单支付下单
     * @param string $out_trade_no 订单号
     * @param integer $payment_code 支付方式 1：支付宝 2：微信
     * @param integer $goods_id 商品Id
     * @param string $user_name 用户名称
     * @return array
     */
    public static function order($out_trade_no, $payment_code, $amount, $product_name, $goods_id)
    {
        if (!in_array($payment_code, [1, 2])) {
            return [
                'code' => 0,
                'message' => '不受支持的支付方式'
            ];
        }
        $payCode = [
            1 => 'alipaywap',
            2 => 'wxwap'
        ];
        $payType = $payCode[$payment_code];

        $data = [
            'sdorderno' => $out_trade_no,
            'appid' => config('fxpay.appid'),
            'goods_id' => $goods_id,
            'sign' => md5(config('fxpay.appid').$out_trade_no.$goods_id.config('fxpay.appSecret')),
            'paytype' => $payType,
            'time' => time(),
            'buyers' => auth('api')->user()->name,
            'client_ip' => request()->ip()
        ];

        //请求
        $response = Http::asForm()->post('https://www.fuxinka.com/platform/api/add', $data);
        $res = $response->json();

        if ($res['status'] !== 200) {
            return [
                'code' => 0,
                'message' => $res['error']
            ];
        } else {
            $data = [
                'qr_url' => config('app.url').'/pay_qr_code?qr_code='.urlencode($res['result']),
                'h5' => $res['result']
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
        $localSign = md5(config('fxpay.appid').$data['sdorderno'].$data['goods_id'].config('fxpay.appSecret'));
        if ($localSign === $data['sign']){
            return true;
        }
        return false;
    }
}
