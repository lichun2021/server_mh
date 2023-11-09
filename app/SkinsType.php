<?php


namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

/**
 * 品质
 * Class SkinsType
 * @package App\Services
 * @author 春风 <860646000@qq.com>
 */
class SkinsType extends Model
{
    /**
     * @var string 数据表
     */
    protected $table = 'skins_type';

    /**
     * @var bool 关闭时间更新
     */
    public $timestamps = false;

    /**
     * @var array
     */
    public static $fields = [
        'cacheKey' => [
            0 => 'skins_type_list', //类型缓存
            1 => 'lucky_type_list' //幸运开箱分类缓存
        ],
    ];


    /**
     * 下拉选择
     * @return array
     */
    public static function downList()
    {
        $types = Cache::get(self::$fields['cacheKey'][0]);
        if ($types === null){
            $types = self::pluck('name','id')->toArray();
            Cache::put(self::$fields['cacheKey'][0],$types);
        }
        return $types;
    }

    /**
     * 外观图片地址补全
     *
     * @return string
     */
    public function getCoverAttribute($value)
    {
        return  config('filesystems.disks.common.url').'/'.$value;
    }

    /**
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model){
            $isUse = Skins::query()->where(['type' => $model->id])->exists();
            if ($isUse){
                throw new \Exception('饰品中有使用此数据的记录！');
            }
            return true;
        });
    }
}
