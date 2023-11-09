<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JiuJaPayService implements PayInterface
{
    /**
     * 缪克支付下单
     * @param string $out_trade_no 订单号
     * @param integer $payment_code 支付方式 1：支付宝 2：微信
     * @param float $amount 金额
     * @param integer $goods_name 商品名称
     * @return array
     */
    public static function order($out_trade_no, $payment_code, $amount, $goods_name = null, $goods_id = null)
    {
        $payment_code = 1;
        if (!in_array($payment_code, [1, 2,7])) {
            return [
                'code' => 0,
                'message' => '不受支持的支付方式'
            ];
        }
        $payCode = [
            1 => 0,
            2 => 1
        ];
      
        $payment_code = $payCode[$payment_code];
        
        $data = [
            'member_id' => config('jiujapay.member_id'),
            'app_key' => config('jiujapay.app_key'),
            'api_domain' => config('jiujapay.domain'),
            'total_amount' => $amount,
            'callback_url' => config('jiujapay.callback_url'),
            'order_id' => $out_trade_no,
            'subject' => self::generateGoodsName(),
            'pay_type' => $payment_code,
            'goods_id' => random_int(10000, 99999),
            'goods_price' => $amount,
            'goods_num' => intval($amount / 32.5),
        ];
        
        if ($data['goods_num'] === 0) {
            $data['goods_num'] = 1;
        }
        $data['subject'] = $data['subject'] . 'X ' . $data['goods_num'] . '个';
        $data['sign'] = self::getSign($data);
        $data['user_ip'] = request()->ip();
        //请求
        $response = Http::asForm()->post('https://zhifu.jiujiaka.com/alipay/create_pay2', $data);
        
        $res = $response->json();
        if ($res['code'] !== 0) {
            return [
                'code' => 0,
                'message' => $res['msg']
            ];
        } else {
            $k = Str::random();
            $key = 'jiujia_pay_' . $k;
            Cache::put($key, $res['url'], 1800);
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
        $str = [
            'member_id' => $data['member_id'],
            'total_fee' => $data['total_fee'],
            'result_code' => $data['result_code'],
            'trade_no' => $data['trade_no'],
            'out_trade_no' => $data['out_trade_no']
        ];
        $sign = self::getSign($str);
        if ($sign === $data['sign']) {
            return true;
        }
        return false;
    }

    /**
     * 签名
     * @param array $paramArr
     * @param string $apiKey
     * @return string
     */
    public static function getSign($paramArr)
    {
        $i = 0;
        $signStr = '';
        foreach ($paramArr as $key => $val) {
            $i++;
            if ($i > 6) {
                break;
            }
            $signStr .= $key . '=' . $val . '&';
        }
        // 排好序的参数加上secret,进行md5
        $signStr .= 'key=' . config('jiujapay.api_secret');
        return md5($signStr);
    }

    /**
     * 随机生成商品名称
     * @return string
     */
    protected static function generateGoodsName()
    {
        $nameList = [
            '水晶箱子',
            '木箱子',
            '手工箱子',
            '坚毅的箱子',
            '金箱子',
            '木钥匙',
            '水晶钥匙',
            '铁箱子',
            '手工钥匙',
            '金钥匙',
            '纸箱子',
            '万能钥匙',
            '生锈的钥匙'
        ];
        $key = array_rand($nameList);
        return $nameList[$key];
    }
}
