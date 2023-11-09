<?php

namespace App\Services;

use App\Admin\Actions\User\Log;
use Illuminate\Support\Facades\Http;

/**
 * Class YouPinService
 * NameSpace App\Services
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2023/6/3
 * Time：17:00
 */
class YouPinService
{
    /**
     * 密钥长度
     */
    private static $KEYSIZE = 2048;

    private static $gateway = 'http://gw-openapi.youpin898.com';

    /**
     * @param string|null $orderNo
     * @param string|null $merchantNo
     * @return array|mixed
     */
    public static function orderStatus($orderNo = null, $merchantNo = null)
    {
        $data = [];
        if ($orderNo !== null) {
            $data['orderNo'] = $orderNo;
        }
        if ($merchantNo !== null) {
            $data['merchantNo'] = $merchantNo;
        }
        return self::http('/open/v1/api/orderStatus', $data);
    }

    /**
     * 取消订单
     * @param string $orderNo 订单号
     * @return array|mixed
     */
    public static function orderCancel($orderNo)
    {
        $data = [
            'orderNo' => $orderNo
        ];
        return self::http('/open/v1/api/orderCancel', $data);
    }

    /**
     * 指定商品购买
     * @param string $merchantOrderNo 商户订单号
     * @param string $steamUrl 收货方的steam交易链接
     * @param string $commodityId 商品ID
     * @param string $purchasePrice 购买价格 单位：元
     * @return array|mixed
     */
    public static function byGoodsIdCreateOrder($merchantOrderNo, $steamUrl, $commodityId, $purchasePrice)
    {
        $data = [
            'merchantOrderNo' => $merchantOrderNo,
            'tradeLinks' => $steamUrl,
            'commodityId' => $commodityId,
            'purchasePrice' => $purchasePrice
        ];
        return self::http('/open/v1/api/byGoodsIdCreateOrder', $data);
    }

    /**
     * 指定模板购买
     * @param string $merchantOrderNo 商户订单号
     * @param string $steamUrl 收货方的steam交易链接
     * @param string|int $commodityTemplateId 商品模板ID或商品模板hashname
     * @param String $purchasePrice 购买价格 单位：元
     * @return array|mixed
     */
    public static function byTemplateCreateOrder($merchantOrderNo, $steamUrl, $commodityTemplateId, $purchasePrice)
    {
        $data = [
            'merchantOrderNo' => $merchantOrderNo,
            'tradeLinks' => $steamUrl,
            'purchasePrice' => $purchasePrice
        ];
        if (is_numeric($commodityTemplateId)) {
            $data['commodityTemplateId'] = $commodityTemplateId;
        } else {
            $data['commodityHashName'] = $commodityTemplateId;
        }
        return self::http('/open/v1/api/byTemplateCreateOrder', $data);
    }

    /**
     * 批量查询在售商品详情
     * @param int $typeId 类型
     * @param int $page 页码
     * @param int $pageSize 每页显示数量
     * @return array|mixed
     */
    public static function queryTemplateSaleByCategory($typeId, $page, $pageSize = 200)
    {
        $data = [
            'typeId' => $typeId,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return self::http('/open/v1/api/queryTemplateSaleByCategory', $data);
    }

    /**
     * 批量查询在售商品价格
     * @param array $requestList 模板id或模板hashName
     * @return array|mixed
     */
    public static function batchGetOnSaleCommodityInfo($requestList)
    {
        $data = [
            'requestList' => $requestList
        ];
        return self::http('/open/v1/api/batchGetOnSaleCommodityInfo', $data);
    }

    /**
     * 查询在售价格
     * @param string|int $templateId 商品模板id或商品模板hashName
     * @return array|mixed
     */
    public static function queryOnSaleCommodityInfo($templateId)
    {
        $data = [

        ];
        if (is_numeric($templateId)) {
            $data['templateId'] = $templateId;
        } else {
            $data['templateHashName'] = $templateId;
        }
        return self::http('/open/v1/api/queryOnSaleCommodityInfo', $data);
    }

    /**
     * 查询商品列表
     * @param string|int $templateId 商品模板id或商品模板hashName
     * @param int $pageSize 每页查询数量
     * @param int $page 页码
     * @param int $sortType 排序方式：0，更新时间倒序； 1，价格升序； 2，价格降序。
     * @return array|mixed
     */
    public static function goodsQuery($templateId, $pageSize = 50, $page = 1, $sortType = 1)
    {
        $data = [

        ];
        if (is_numeric($templateId)) {
            $data['templateId'] = $templateId;
        } else {
            $data['templateHashName'] = $templateId;
        }
        $data['pageSize'] = $pageSize;
        $data['page'] = $page;
        $data['sortType'] = $sortType;
        return self::http('/open/v1/api/goodsQuery', $data);
    }

    /**
     * 查询模板ID
     * @return array|mixed
     */
    public static function templateQuery()
    {
        return self::http('/open/v1/api/templateQuery');
    }

    /**
     * 验证交易链接
     * @param string $steam_url 交易链接
     * @return array|mixed
     */
    public static function checkTradeUrl($steam_url)
    {
        return self::http('/open/v1/api/checkTradeUrl', [
            'tradeLinks' => $steam_url
        ]);
    }

    /**
     * 余额查询
     * @return array|mixed
     */
    public static function getAssetsInfo()
    {
        $key = 'youpin898_getAssetsInfo';
        $data = \Cache::get($key);
        if ($data === null) {
            $data = self::http('/open/v1/api/getAssetsInfo');
            \Cache::put($key, $data, 60);
        }
        return $data;
    }

    /**
     * 签名验证
     * @return bool
     */
    public static function verifySignature()
    {
        $data = request()->post();
        $signParam = $data;
        unset($signParam['sign']);
        ksort($signParam);
        $str = "";
        foreach ($signParam as $k => $v) {
            $str .= $k . $v;
        }
        $publicKeyString = config('youpin.youPinPublicKey');
        $pubPem = chunk_split($publicKeyString, 64, "\n");
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . $pubPem . "-----END PUBLIC KEY-----\n";
        $publicKeyObj = openssl_pkey_get_public($publicKey);
        $success = openssl_verify($str, base64_decode($data['sign']), $publicKeyObj, OPENSSL_ALGO_SHA256);
        return $success === 1 ? true : false;
    }

    /**
     * 回调响应内容
     * @param string $msg
     * @param string $messageNo
     * @param bool $bool
     * @return array
     */
    public static function response($messageNo, $bool, $msg = '成功')
    {
        return [
            'code' => 200,
            'msg' => $msg,
            'data' => [
                'messageNo' => $messageNo,
                'flag' => $bool
            ]
        ];
    }

    /**
     * 统一请求
     * @param string $url
     * @param array $param
     * @return array|mixed
     */
    private static function http($url, $param = [])
    {
        $param['appKey'] = config('youpin.appkey');
        $param['timestamp'] = date('Y-m-d H:i:s');
        $data = self::SignParam($param);
        $resp = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0'
        ])->timeout(30)
            ->baseUrl(self::$gateway)
            ->post($url, $data)
            ->json();
        return $resp;
    }

    /**
     * 用私钥对信息进行数字签名
     * @param string $data 加密数据
     * @return string
     * @throws \RuntimeException
     *
     */
    private static function SignByPrivateKey($data, $privateKey)
    {
        $key = openssl_pkey_get_private($privateKey);
        if (!$key) {
            throw new \RuntimeException('Fail to get private key');
        }
        // 用私钥对信息进行数字签名
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    /**
     * 对参数进行签名
     *
     * @param array $param 请求参数
     * @return array
     */
    private static function SignParam($param)
    {
        $privateKey = config('youpin.privateKey');
        $priPem = chunk_split($privateKey, 64, "\n");
        $priPem = "-----BEGIN PRIVATE KEY-----\n" . $priPem . "-----END PRIVATE KEY-----\n";

        unset($param['sign']);
        ksort($param);
        $str = "";
        foreach ($param as $k => $v) {
            $str .= $k . json_encode($v, JSON_UNESCAPED_SLASHES);
        }
        $ret = self::SignByPrivateKey($str, $priPem);
        $param['sign'] = $ret;

        return $param;
    }

    /**
     * 生成密钥对
     *
     * @return array 密钥对象；返回的为原始字符串格式，非pem格式
     * @throws \Exception
     *
     */
    public static function GenerateKey()
    {
        $keygen = \openssl_pkey_new([
            "private_key_bits" => self::$KEYSIZE,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($keygen, $private_key);
        $public_key = openssl_pkey_get_details($keygen)["key"];

        $private_key = str_replace(array("-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\n"), "", $private_key);
        $public_key = str_replace(array("-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\n"), "", $public_key);

        return array('public' => $public_key, 'private' => $private_key);
    }
}
