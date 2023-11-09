<?php

use App\User;
use App\BoxRecord;
use App\Common\ConfigClass;

/**
 * 获取配置
 * @param $code string 配置键
 * @return mixed|null
 */
function getConfig($code)
{
    return ConfigClass::getConfig($code);
}

/**
 * 随机取区间值
 * @param int $min
 * @param int $max
 * @return float|int
 */
function randFloat($min = 0, $max = 1)
{
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

/**
 * 获取网站全部配置
 * @return array
 */
function getConfigAll($reread = false)
{
    return ConfigClass::getConfigAll($reread);
}

/**
 * @param $key string
 * @return mixed
 */
function getCacheKey($key)
{
    return ConfigClass::$CacheKey[$key];
}

/**
 * 生产唯一订单号
 * @return bool|string
 * @throws \Exception
 */
function getUniqueOrderNumber()
{
    $prefix = date('YmdHis');
    for ($i = 0; $i < 20; $i++) {
        // 随机生成 6 位的数字，并创建订单号
        $no = $prefix . random(4,true);
        // 判断是否已经存在
        if (!BoxRecord::where('code', $no)->exists()) {
            return $no;
        }
    }
    //写入日志
    \Log::error('订单号重复');
    return false;
}

/**
 * 生成唯一邀请码
 * @param integer $user_id
 * @return bool|string
 */
function getInviteCode($user_id)
{
    $multiple = 10;
    for ($i = 0; $i < 20; $i++) {
        $multiple++;
        $invite_code = strtoupper(base_convert($user_id * $multiple, 10, 16));
        if (!User::where('invite_code', $invite_code)->exists()) {
            return $invite_code;
        }
    }
    \Log::error('重复邀请码',[$invite_code]);
    return false;
}

/**
 * 对战开奖号码
 * @return string
 */
function getGameDrawCode()
{
    $prefix = date('YmdHis');
    return $prefix.random(4,true);
}

/**
 * 随机字符串
 * @param $length
 * @param bool $numeric
 * @return string
 */
function random($length, $numeric = false)
{
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    if (!$numeric) {
        $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
        $length--;
    }
    $max = strlen($seed) - 1;
    $seed = str_split($seed);
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * @param string $begin  开始字符串
 * @param string $end    结束字符串
 * @param string $str    需要截取的字符串
 * @return string
*/
function cut($begin,$end,$str){
    $b = mb_strpos($str,$begin) + mb_strlen($begin);
    $e = mb_strpos($str,$end) - $b;
    return mb_substr($str,$b,$e);
}
