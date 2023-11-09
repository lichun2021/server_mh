<?php

/**
 * ZBT逻辑类
 */

namespace App\Services;

use App\ZbtApi;
use App\SteamItem;
use Illuminate\Support\Facades\Http;

class ZbtService
{
    /**
     * 查看商品
     *
     * @return void
     */
    public static function product(int $page = 1)
    {
        $data = [
            'app-key' => config('zbt.api_key'),
            'appId' => config('zbt.csgo_id'),
            'limit' => 20,
            'page' => $page
        ];
        $response = Http::get('https://app.zbt.com/open/product/v2/search', $data);

        return $response->json();
    }


    /**
     * 根据装备名称 获取所有在售
     * @param string $item_name 装备名称
     * @return array
     */
    public static function info(string $item_name)
    {
        $steam_item = SteamItem::where('item_name', $item_name)->first();
        if (!$steam_item) {
            return [
                'title' => '本地Steam 错误',
                'code' => 0,
                'message' => 'Steam 数据库里没有找到此物品，请补数据！'
            ];
        }

        $data = [
            'appId' => config('zbt.csgo_id'),
            'marketHashNameList' => [$steam_item['market_hash_name']]
        ];
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);

        $response = Http::post('https://app.zbt.com/open/product/price/info?' . $param, $data);
        $result = $response->json();
        if ($result['success']) {
            return static::sell_list($result['data'][0]['itemId']);
        }
        return [
            'code' => 0,
            'title' => 'Api接口错误',
            'message' => $result['errorMsg']
        ];
    }

    public static function OpenProductPriceInfo($item_name = [])
    {
        $data = [
            'appId' => config('zbt.csgo_id'),
            'marketHashNameList' => $item_name
        ];
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);

        $response = Http::post('https://app.zbt.com/open/product/price/info?' . $param, $data);
        return $response->json();
    }

    /**
     * 获取某一个饰品的所有在售
     * @param int $itemId
     * @return array
     */
    public static function sell_list($itemId, $page = 1, $per_page = 20, $delivery = null)
    {
        $param = [
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN',
            'itemId' => $itemId,
            'orderBy' => 2,
            'limit' => $per_page,
            'page' => $page,
        ];
        if ($delivery !== null) {
            $param['delivery'] = $delivery;
        }

        $response = Http::get('https://app.zbt.com/open/product/v1/sell/list', $param);
        $result = $response->json();

        if ($result['success']) {
            return [
                'code' => 1,
                'message' => $result
            ];
        }
        return [
            'code' => 0,
            'message' => $result['errorMsg']
        ];
    }

    /**
     * 快速购买
     * @param string $out_trade_no 订单号
     * @param string $itemId 扎比特饰品Id
     * @param string $max_bean 最大T币
     * @param string $trade_url Steam交易链接
     * @return array
     */
    public static function quick_buy(string $out_trade_no, string $itemId, string $max_bean, string $trade_url)
    {

        $data = [
            'appId' => config('zbt.csgo_id'),
            'itemId' => $itemId,
            'lowPrice' => 1,
            'maxPrice' => $max_bean,
            'outTradeNo' => $out_trade_no,
            'tradeUrl' => $trade_url
        ];
        if (getConfig('send_skins_type') > 0) {
            $data['delivery'] = getConfig('send_skins_type');
        }

        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);
        $response = Http::timeout(20)->post('https://app.zbt.com/open/trade/v2/quick-buy?' . $param, $data);
        $result = $response->json();
        
        if (!is_array($result) || !array_key_exists('success', $result) || !array_key_exists($response->status(), ZbtApi::$fields['statusCode'])) {
            throw new \Exception('ZBT发货错误，没有返回success字段或不是标准的Json：' . $response->body() . ' 状态码：' . $response->status());
        }
        
        if ($result['success']) {
            return [
                'code' => 1,
                'message' => '下单成功',
                'data' => $result['data']
            ];
        }

        return [
            'code' => 0,
            'message' => $result['errorMsg']
        ];
    }

    /**
     * 普通购买
     * @param $out_trade_no
     * @param $productId
     * @param $trade_url
     * @return array
     */
    public static function buy($out_trade_no, $productId, $trade_url)
    {
        $data = [
            'productId' => $productId,
            'outTradeNo' => $out_trade_no,
            'tradeUrl' => $trade_url,
        ];

        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);
        $response = Http::post('https://app.zbt.com/open/trade/v2/buy?' . $param, $data);
        $result = $response->json();

        if (!is_array($result) || !array_key_exists('success', $result) || !array_key_exists($response->status(), ZbtApi::$fields['statusCode'])) {
            throw new \Exception('ZBT发货错误，没有返回success字段或不是标准的Json：' . $response->body() . ' 状态码：' . $response->status());
        }
        
        if ($result['success']) {
            return [
                'code' => 1,
                'price' => $result['data']['buyPrice'],
                'delivery' => $result['data']['delivery'],
                'order_id' => $result['data']['orderId'],
                'message' => '发货成功'
            ];
        }

        return [
            'code' => 0,
            'message' => $result['errorMsg']
        ];
    }


    /**
     * 查询余额
     * @return array
     */
    public static function check_balance()
    {
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);

        $response = Http::get('https://app.zbt.com/open/user/v1/t-coin/balance?' . $param);
        $result = $response->json();

        if ($result['success']) {
            return [
                'code' => 1,
                'message' => $result
            ];
        }

        return [
            'code' => 0,
            'message' => $result['errorMsg']
        ];
    }

    /**
     * 创建Steam账号状态检测
     * @param string $steam_url Steam链接地址
     * @return array
     */
    public static function steam_check(string $steam_url)
    {
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);
        $data = [
            'appId' => config('zbt.csgo_id'),
            'tradeUrl' => $steam_url,
            'type' => 1
        ];
        $response = Http::post('https://app.zbt.com/open/user/steam-check/create?' . $param, $data);
        $result = $response->json();

        if ($result['success']) {
            return [
                'code' => 1,
                'message' => '检测成功'
            ];
        }

        return [
            'code' => 0,
            'message' => $result['errorMsg']
        ];
    }

    /**
     * 查询Steam账号状态
     * @param $steam_url
     * @return array
     */
    public static function steam_info($steam_url)
    {
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'appId' => 730,
            'language' => 'zh_CN',
            'type' => 1,
            'tradeUrl' => $steam_url
        ]);
        $response = Http::get('https://app.zbt.com/open/user/steam-info?' . $param);
        $result = $response->json();
        if ($result['success']) {
            return [
                'code' => 1,
                'message' => 'OK',
                'data' => $result['data']
            ];
        }

        return [
            'code' => 0,
            'message' => $result['errorMsg']
        ];
    }

    /**
     * 购买订单详情
     * @param integer $orderId
     * @return array
     */
    public static function detail($orderId)
    {
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN',
            'orderId' => $orderId
        ]);

        $response = Http::get('https://app.zbt.com/open/order/v2/buy/detail?' . $param);
        $result = $response->json();

        if ($result['success']) {
            return [
                'code' => 1,
                'data' => $result['data']
            ];
        }

        return [
            'code' => 0,
            'message' => $result['errorMsg']
        ];
    }

    /**
     * 取消订单
     * @param $orderId
     * @return array
     */
    public static function orderCancel($orderId)
    {
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);
        $data = [
            'orderId' => $orderId
        ];
        $response = Http::post('https://app.zbt.com/open/order/buyer-cancel?' . $param, $data);
        $result = $response->json();

        if ($result['success'] === false) {
            return [
                'code' => 0,
                'message' => $result['errorMsg']
            ];
        } elseif ($result['data']['successNum'] < 1) {
            return [
                'code' => 0,
                'message' => '订单状态不支持取消操作！'
            ];
        }
        return [
            'code' => 1,
            'message' => '订单取消成功！'
        ];
    }

    /**
     * @return mixed
     */
    public static function open_development_info()
    {
        $param = http_build_query([
            'app-key' => config('zbt.api_key'),
            'language' => 'zh_CN'
        ]);

        $response = Http::get('https://app.zbt.com/open/development/info?' . $param);
        $result = $response->json();

        return $result;
    }
}
