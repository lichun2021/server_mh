<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HnsqPayService
{
    public static function order($out_trade_no, $payment_code, $amount, $goods_name, $goods_id)
    {
        if (!in_array($payment_code, [1, 2])) {
            return [
                'code' => 0,
                'message' => '不受支持的支付方式！'
            ];
        } elseif ($payment_code == 2) {
            return [
                'code' => 0,
                'message' => '微信支付通道暂不可用！'
            ];
        }
        $payCode = [
            1 => '002',
            2 => 1
        ];
        $payment_code = $payCode[$payment_code];
        $data = [
            'userid' => config('hnsqpay.userid'),
            'orderno' => $out_trade_no,
            'title' => $goods_name,
            'paycode' => $payment_code,
            'notify_url' => config('hnsqpay.notify_url'),
            'return_url' => config('hnsqpay.return_url'),
            'amount' => intval($amount) == $amount ? intval($amount) : $amount,
        ];
        $signData = $data;
        unset($signData['paycode']);
        $data['sign'] = self::getSign($signData, config('hnsqpay.pay_key'));

        $resp = Http::post('http://www.hnsqpay.com/pay/api', $data);
        $res = $resp->json();

        if (!array_key_exists('errcode', $res)) {
            return [
                'code' => 0,
                'message' => '系统错误，请联系管理员！'
            ];
        } elseif ($res['errcode'] !== 0) {
            return [
                'code' => 0,
                'message' => $res['errmsg']
            ];
        } else {
            $k = Str::random();
            $key = 'hnsqpay_pay_' . $k;
            Cache::put($key, $res['data'], 1800);
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
    }

    /**
     * 验证签名
     * @return bool
     */
    public static function verifySign()
    {
        $data = request()->post();
        $signData = $data;
        unset($signData['sign'], $signData['errcode'], $signData['transaction']);
        $signData['userid'] = config('hnsqpay.userid');
        $sign = self::getSign($signData, config('hnsqpay.notify_key'));
        if ($sign === $data['sign']) {
            return true;
        }
        return false;
    }

    /**
     * @param $data
     * @return string
     */
    public static function getSign($data, $key)
    {
        ksort($data);
        return md5(http_build_query($data) . $key);
    }
}
