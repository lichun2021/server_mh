<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/15
 * Time: 21:23
 */

namespace App\Services;

/**
 * 支付接口规范
 * Interface PayInterface
 * @package App\Services
 */
interface PayInterface
{
    /**
     * @param string $out_trade_no 订单号
     * @param int $payment_code 支付通道代码 1支付宝 2微信
     * @param float $amount 金额
     * @param string $product_name 商品名称
     * @param int $goods_id 商品Id
     * @return mixed
     */
    public static function order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
}
