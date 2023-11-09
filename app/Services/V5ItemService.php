<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Class V5ItemService
 * NameSpace App\Services
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2023/8/9
 * Time：16:46
 */
class V5ItemService
{
    public static $gateway = 'https://delivery.v5item.com';

    /**
     * 取消采购订单
     * @param string|int $merchantOrderNo
     * @return array|mixed
     */
    public static function cancelOrder($merchantOrderNo)
    {
        $resp = self::http('/open/api/cancelOrder', [
            'merchantOrderNo' => $merchantOrderNo,
            'merchantKey' => config('v5item.merchantKey'),
        ]);
        return $resp;
    }

    /**
     * 查询采购订单当前状态
     * @param string|int $merchantOrderNo
     * @return array|mixed
     */
    public static function queryOrderStatus($merchantOrderNo)
    {
        $resp = self::http('/open/api/queryOrderStatus', [
            'merchantOrderNo' => $merchantOrderNo,
            'merchantKey' => config('v5item.merchantKey'),
        ]);
        return $resp;
    }

    /**
     * 指定饰品模板创建采购订单
     * @param string $HashName 饰品HashName
     * @param double $maxPrice 最高价
     * @param string $tradeUrl Steam交易链接
     * @param string|integer $merchantOrderNo 订单号
     * @return array|mixed
     */
    public static function createOrderByMarketHashName($HashName, $maxPrice, $tradeUrl, $merchantOrderNo)
    {
        $resp = self::http('/open/api/createOrderByMarketHashName', [
            'marketHashName' => $HashName,
            'purchasePrice' => $maxPrice,
            'tradeUrl' => $tradeUrl,
            'merchantOrderNo' => $merchantOrderNo,
            'merchantKey' => config('v5item.merchantKey')
        ]);
        return $resp;
    }

    /**
     * 查询余额
     * @return array|mixed
     */
    public static function checkMerchantBalance()
    {
        $resp = self::http('/open/api/checkMerchantBalance', [
            'merchantKey' => config('v5item.merchantKey')
        ]);
        return $resp;
    }

    /**
     * 获得Token
     * @return array|mixed
     * @throws \Exception
     */
    private static function getToken()
    {
        $key = 'v5item_tradeToken';
        $token = \Cache::get($key);
        if ($token === null) {
            $resp = self::http('/open/api/queryMerchantInfo', [
                'account' => config('v5item.account'),
                'password' => config('v5item.password'),
            ]);
            if ($resp['code'] !== 0) {
                throw new \Exception($resp['msg']);
            }
            $token = $resp['data']['tradeToken'];
            \Cache::put($key, $token, 604800);
        }
        return $token;
    }

    /**
     * 统一请求
     * @param string $url
     * @param array $param
     * @return array|mixed
     */
    private static function http($url, $param = [])
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0',
        ];
        if ($url !== '/open/api/queryMerchantInfo') {
            $headers['Authorization'] = self::getToken();
        }
        $resp = Http::withHeaders($headers)->timeout(30)
            ->baseUrl(self::$gateway)
            ->post($url, $param);
        return $resp->json();
    }
}
