<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class VggStorePayService implements PayInterface
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
            'userid' => config('vggstorepay.userid'),
            'orderno' => $out_trade_no,
            'title' => $goods_name,
            'paycode' => $payment_code,
            'notify_url' => config('vggstorepay.notify_url'),
            'return_url' => config('vggstorepay.return_url'),
            'amount' => intval($amount) == $amount ? intval($amount) : $amount,
            'pay_ip' => request()->ip(),
        ];
        $signData = $data;
        unset($signData['paycode'], $signData['regist_time'], $signData['pay_ip']);
        $data['sign'] = self::getSign($signData, config('vggstorepay.pay_key'));
        $postHtml = <<<EOF
<form action="https://www.vggstore.com/pay/api" method="post" id="{$out_trade_no}">
	<input type="hidden" name="userid" value="{$data['userid']}">
	<input type="hidden" name="notify_url" value="{$data['notify_url']}">
	<input type="hidden" name="return_url" value="{$data['return_url']}">
	<input type="hidden" name="paycode"  value="{$data['paycode']}" >
	<input type="hidden" name="title"  value="{$data['title']}" >
	<input type="hidden" name="amount"  value="{$data['amount']}" >
	<input type="hidden" name="orderno"  value="{$data['orderno']}" >
	<input type="hidden" name="sign"  value="{$data['sign']}" >
</form>
<script type="text/javascript">
	document.getElementById("{$out_trade_no}").submit();
</script>
EOF;
        $key = 'vgg_store_pay_' . $out_trade_no;
        Cache::put($key, $postHtml, 1800);
        $pay_url = config('app.url') . '/to?key=' . $out_trade_no;
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
     * 验证签名
     * @return bool
     */
    public static function verifySign()
    {
        $data = request()->post();
        $signData = $data;
        unset($signData['sign'], $signData['errcode'], $signData['transaction']);
        $signData['userid'] = config('vggstorepay.userid');
        $sign = self::getSign($signData, config('vggstorepay.notify_key'));
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
