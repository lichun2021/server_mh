<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/22 10:39
 */

namespace App\Common;

use App\SystemConfig;
use Illuminate\Support\Facades\Cache;

class ConfigClass
{
    /**
     * 全站缓存KEY
     * @var array
     */
    public static $CacheKey = [
        'config' => 'common:config',
        'LuckyOpenBoxList' => 'luckyopenbox:list_',
        'LuckyOpenBoxAward' => 'luckyopenbox:award_',
        'gameArenaBoxList' => 'gamearena:box_list',
        'gameArenaBox' => 'gamearena:box_'
    ];

    /**
     * 缓存原子锁KEY
     * @var array
     */
    public static $LockKey = [

    ];

    /**
     * @var int 缓存默认过期时间
     */
    private static $CacheTime = 3600;

    /**
     * @var array 配置
     */
    private static $config = [];


    /**
     * 获取单个配置项
     * @param $code string 配置键
     * @return mixed|null
     */
    public static function getConfig($code)
    {
        $configs = self::getConfigAll();
        if (isset($configs[$code])){
            return $configs[$code];
        }
        $configs = self::getConfigAll(true);
        return isset($configs[$code]) ? $configs[$code]:null;
    }

    /**
     * 获取全站配置
     * @return array
     */
    public static function getConfigAll($reread = false)
    {
        if (!empty(self::$config && $reread === false)){
            return self::$config;
        } else {
            self::$config = Cache::get(self::$CacheKey['config']);
            if (self::$config === null || $reread === true){
                $config = SystemConfig::select(['code','value','type'])->get()->toArray();
                $configs = [];
                foreach ($config as $item){
                    $configs[$item['code']] = $item['value'];
                }
                Cache::put(self::$CacheKey['config'],$configs,self::$CacheTime);
            }
        }
        return self::$config;
    }
}
