<?php

namespace App\Services;

use App\StarsContain;
use App\StarsList;
use Illuminate\Support\Facades\Redis;

/**
 * Class StarWarsService
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/28
 * Time：23:41
 */
class StarWarsService
{
    public static function getSkin($star_id, $num)
    {
        $key = self::getJackpotKey($star_id, $num);
        $methods = ['rpop', 'lpop'];
        $randInt = array_rand($methods, 1);
        $method = $methods[$randInt];
        $skinId = Redis::$method($key);
        if ($skinId === false) {
            Redis::lpush($key, ...self::getSkinsList($star_id, $num));
            return self::getSkin($star_id, $num);
        }
        return $skinId;
    }

    /**
     * 奖池生成
     * @param $star_id
     * @param $num
     * @return array
     * @throws \Exception
     */
    public static function getSkinsList($star_id, $num)
    {
        $data = [];
        if (auth('api')->user()->anchor === 1) {
            $s = 'a' . $num;
        } else {
            $s = 'u' . $num;
        }
        $lv = 'l' . $num;
        $contains = StarsContain::select(['id', 'stars_id', 'skin_id', $s, $lv])
            ->where(['stars_id' => $star_id])
            ->get()
            ->toArray();

        foreach ($contains as $contain) {
            for ($i = 0; $i < $contain[$s]; $i++) {
                $data[] = $contain['skin_id'] . '|' . $contain[$lv];
            }
        }
        if (empty($data)) {
            throw new \Exception('此红星轮盘正在维护，请更换其他红星轮盘，给您带来不便请谅解！');
        }
        shuffle($data);
        return $data;
    }

    /**
     * 获取缓存KEY
     * @param $star_id
     * @param $user_id
     * @return string
     */
    public static function getCacheKey($star_id, $user_id)
    {
        return StarsList::$fields['cacheKey'][0] . $star_id . '_' . $user_id;
    }

    /**
     * 获取订单Id
     * @param $star_id
     * @param $user_id
     */
    public static function getOrderId($star_id, $user_id)
    {
        $key = self::getCacheKey($star_id, $user_id);
        $orderId = \Cache::get($key);
        if ($orderId === null) {
            $orderId = self::generateOrderId($star_id, $user_id);
            \Cache::put($key, $orderId);
        }
        return $orderId;
    }

    /**
     * 获取奖池Key
     * @param $star_id
     * @param $num
     * @return string
     */
    public static function getJackpotKey($star_id, $num)
    {
        if (auth('api')->user()->anchor === 1) {
            return StarsList::$fields['cacheKey'][1] . 'anchor_' . $star_id . '_' . $num;
        }
        return StarsList::$fields['cacheKey'][1] . 'user_' . $star_id . '_' . $num;
    }

    /**
     * 生成订单Id
     * @param $star_id
     * @param $user_id
     */
    protected static function generateOrderId($star_id, $user_id)
    {
        $date = date('YmdHis');
        $rand = rand(100000, 999999);
        return md5($date . $rand . $star_id . $user_id);
    }
}
