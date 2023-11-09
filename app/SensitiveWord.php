<?php


namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
class SensitiveWord extends Model
{
    /**
     * @var bool 关闭时间更新
     */
    public $timestamps = false;

    /**
     * 缓存Key
     * @var string
     */
    public static $cacheKey = 'sensitive_word_list';

    /**
     * 获取全部敏感词
     * @return array|mixed
     */
    public static function getWords()
    {
        $words = Cache::get(self::$cacheKey);
        if ($words === null){
            $words = self::query()->pluck('word')->toArray();
            Cache::put(self::$cacheKey,$words);
        }
        return $words;
    }
}
