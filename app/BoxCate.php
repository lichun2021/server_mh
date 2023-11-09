<?php


namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

/**
 * 宝箱分类
 * Class BoxCate
 * @package App
 * @author 春风 <860646000@qq.com>
 */
class BoxCate extends Model
{

    /**
     * @var array
     */
    public static $fields = [
        'cacheKey' => 'box_cate_list'
    ];

    /**
     * 获取宝箱分类
     * @return mixed|array
     */
    public static function getList()
    {
        $key = self::$fields['cacheKey'];
        $cateList = Cache::get($key);
        if ($cateList === null){
            $cateList = self::pluck('name','id')->toArray();
            Cache::put($key,$cateList);
        }
        return $cateList;
    }

    /**
     * 关联宝箱
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function box()
    {
        return $this->hasMany(Box::class,'cate_id','id');
    }

    /**
     * <访问器> 背景图片
     *
     * @return string
     */
    public function getSrcAttribute($value)
    {
        return $value ? config('filesystems.disks.common.url') .'/'. $value : '';
    }

    /**
     * 格式化时间
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model){
            $isUse = Box::where(['cate_id' => $model->id])->exists();
            if ($isUse){
                throw new \Exception('宝箱分类下有数据无法删除！');
            }
            //清理宝箱列表缓存
            \Cache::delete(Box::$fields['cacheKey'][1]);
            //清除分类缓存
            \Cache::delete(BoxCate::$fields['cacheKey']);
            return true;
        });
    }
}
