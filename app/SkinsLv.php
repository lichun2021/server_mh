<?php


namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class SkinsLv extends Model
{
    /**
     * 数据表
     * @var string
     */
    protected $table = 'skins_lv';

    /**
     * @var array
     */
    public static $fields = [
        'cacheKey' => 'skins_lv_list'
    ];

    /**
     * @var bool 关闭时间更新
     */
    public $timestamps = false;

    /**
     * 获取以ID为主键的等级列表
     * @return array|mixed
     */
    public static function getList()
    {
        $key = self::$fields['cacheKey'];
        $lvList = Cache::get($key);
        if ($lvList === null) {
            $lvList = array_column(SkinsLv::get()->toArray(), null, 'id');
            Cache::put($key, $lvList);
        }
        return $lvList;
    }

    /**
     * 等级下拉菜单
     * @return array|mixed
     */
    public static function downList()
    {
        $key = 'skins_lv_down_list';
        $downList = Cache::get($key);
        if ($downList === null) {
            $downList = SkinsLv::pluck('name', 'id')->toArray();
            Cache::put($key, $downList, 1800);
        }
        return $downList;
    }

    /**
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model) {
            $isUse = Skins::where(['lv' => $model->id])->exists();
            if ($isUse) {
                throw new \Exception('饰品中有使用此数据的记录！');
            }
            return true;
        });
    }
}
