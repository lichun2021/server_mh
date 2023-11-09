<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Class SkinsBusService
 * @package App\Services
 * @author 春风 <860646000@qq.com>
 */
class SkinsBusService
{
    /**
     * @var string 接口网关
     */
    private static $gateway = 'https://api.skinsbus.com';

    /**
     * @var string 语言
     */
    private static $language = 'zh-CN';

    /**
     * 基本信息
     * @return mixed
     */
    public static function memberInfo()
    {
        $data = self::publicRequestParameter();
        $response = Http::withOptions([
            'verify' => false
        ])->post(self::$gateway . '/v1/member/info', $data);
        return $response->json();
    }

    /**
     * @param string $market_hash_name
     * @param string $trade_url 收货链接
     * @param float $max_price 购买价格上限
     * @param string $custom_order_no 商户订单号
     * @param null|integer $mode 0自动发货，1人工发货，null全部
     * @return mixed
     */
    public static function orderQuickBuy($market_hash_name, $trade_url, $max_price, $custom_order_no, $mode = null)
    {
        $data = [
            'market_hash_name' => $market_hash_name,
            'trade_url' => $trade_url,
            'max_price' => $max_price,
            'custom_order_no' => $custom_order_no,
            'mode' => $mode,
        ];

        $data = self::publicRequestParameter($data);

        $response = Http::post(self::$gateway . '/v1/order/quick-buy', $data);

        return $response->json();
    }

    /**
     * @param string $start_date 开始时间 开始日期， 如： "2020-09-10"
     * @param string $end_date 结束日期， 如："2020-11-10"
     * @param null $status 订单状态
     * @param int $limit 每页数量
     * @param int $page 页码
     * @return mixed
     */
    public static function orderHistory($start_date, $end_date, $status = null, $limit = 20, $page = 1)
    {
        $data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $status,
            'limit' => $limit,
            'page' => $page
        ];

        $data = self::publicRequestParameter($data);

        $response = Http::post(self::$gateway . '/v1/order/history', $data);

        return $response->json();
    }

    /**
     * 订单记录详情
     * @param null|string $order_no 平台订单号 与custom_order_no必传其一
     * @param null|string $custom_order_no 商户订单号
     * @return mixed
     */
    public static function orderDetail($order_no = null, $custom_order_no = null)
    {
        $data = [
            'order_no' => $order_no,
            'custom_order_no' => $custom_order_no
        ];

        $data = self::publicRequestParameter($data);

        $response = Http::post(self::$gateway . '/v1/order/detail', $data);

        return $response->json();
    }

    /**
     * 取消订单
     * @param string $order_no 平台订单号
     * @return mixed
     */
    public static function orderCancel($order_no)
    {
        $data = [
            'order_no' => $order_no
        ];

        $data = self::publicRequestParameter($data);

        $response = Http::post(self::$gateway . '/v1/order/cancel', $data);

        return $response->json();
    }

    /**
     * 在售饰品筛选
     * @param array $hash_name
     * @param int $page
     * @param int $limit
     * @param null $mode
     * @param null $order_by
     * @return mixed
     */
    public static function marketList($hash_name = [], $page = 1, $limit = 20, $mode = null, $order_by = null)
    {
        $data = [
            'market_hash_name' => implode(',', $hash_name),
            'page' => $page,
            'limit' => $limit,
            'mode' => $mode,
            'is_show_CNY' => 1,
            'order_by' => $order_by
        ];

        $data = self::publicRequestParameter($data);

        $response = Http::post(self::$gateway . '/v1/market/list', $data);

        return $response->json();
    }

    /**
     * 请求参数去空
     * @param $data array
     * @return array
     */
    public static function requestParameterRemovalEmpty($data)
    {
        $parameter = [];
        foreach ($data as $key => $val) {
            if ($val !== '0' && empty($val)) {
                continue;
            }
            $parameter[$key] = $val;
        }
        return $parameter;
    }

    /**
     * 签名
     * @param $data
     * @return string
     */
    public static function getSign($data)
    {
        if (isset($data['api_key'])) {
            unset($data['api_key']);
        }

        if (isset($data['sign'])) {
            unset($data['sign']);
        }

        ksort($data);
        $signStr = '';
        foreach ($data as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        // 去掉最后一个&
        $signStr = substr($signStr, 0, strlen($signStr) - 1);
        // 排好序的参数加上secret,进行md5
        $signStr .= config('skins-bus.api_key');
        return strtoupper(md5($signStr));
    }

    /**
     * 公共请求参数
     * @param array $data
     * @return array
     */
    private static function publicRequestParameter($data = [])
    {
        $publicParameter = [
            'api_key' => config('skins-bus.api_key'),
            'language' => self::$language,
            'timestamp' => time(),
        ];
        $data = array_merge($publicParameter, $data);
        $data = self::requestParameterRemovalEmpty($data);
        $data['sign'] = self::getSign($data);
        return $data;
    }
}
