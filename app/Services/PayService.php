<?php

namespace App\Services;

class PayService
{
    /**
     * @param string $out_trade_no 订单号
     * @param integer $payment_code 支付方式 1：支付宝 2：微信
     * @param float $amount 金额
     * @param string $product_name 产品名称
     * @param integer $goods_id 商品Id
     * @return array|mixed|string|void
     * @throws \Exception
     */
    public static function order($out_trade_no, $payment_code, $amount, $product_name, $goods_id)
    {
       
            
        if ($payment_code == 7) {
            $payName = getConfig('alipay_channel_name');
            
            if ($payName === 'alipay') {
                return AlipayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'jiujiapay') {
                return JiuJaPayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'fxpay') {
                return FxPayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'vggstorepay') {
                return VggStorePayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'hnsqpay') {
                return HnsqPayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            }
            throw new \Exception('支付渠道配置错误');
        } elseif ($payment_code == 2) {
            $payName = getConfig('wechatpay_channel_name');
            /*if ($payName === 'wechatpay') {
                return WechatPayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } */
            //重新支付渠道码
            $payment_code = 1;
            if ($payName === 'alipay') {
                return AlipayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'jiujiapay') {
                return JiuJaPayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'fxpay') {
                return FxPayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'vggstorepay') {
                return VggStorePayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            } elseif ($payName === 'hnsqpay') {
                return HnsqPayService::order($out_trade_no, $payment_code, $amount, $product_name, $goods_id);
            }
            throw new \Exception('支付渠道配置错误');
        }
    }
}
