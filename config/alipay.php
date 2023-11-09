<?php
/**
 * 支付吧支付配置文件
 * @author 春风 <860646000@qq.com>
 */

return [
    /** appId **/
    'app_id' => '',
    /** 私钥 **/
    'merchant_private_key' => '',
    /** 支付宝公钥 **/
    'alipay_public_key' => '',
    /** AES密钥 **/
    'encrypt_key' => '',
    /** 支付成功同步调转地址 **/
    'return_url' => '',
    /** 异步通知接收服务地址 **/
    'notify_url' => 'http:///callback/alipay',
];

