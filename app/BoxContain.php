<?php


namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;

/**
 * 宝箱包含饰品
 * Class BoxContain
 * @package App
 * @author 春风 <860646000@qq.com>
 */
class BoxContain extends Model
{
    public static $fields = [
        //幸运值物品
        'is_luck' => [
            0 => '否',
            1 => '是'
        ],
        //颜色
        'lv' => [
            1 => '金',
            2 => '红',
            3 => '紫',
            4 => '蓝',
            5 => '灰'
        ],
    ];

    /**
     * @var array 分母
     */
    public static $denominator = [];

    /**
     * <访问器> 爆率百分比
     *
     * @return string
     */
    public function getOddsPercentAttribute()
    {
        if ($this->odds == 0) {
            return '0%';
        }
        return bcdiv($this->odds * 100, self::setDenominator($this->box_id), 2) . '%';
    }

    /**
     * 颜色等级名
     * @return string|null
     */
    public function getLevelNameAttribute()
    {
        $levels = SkinsLv::getList();
        if ($this->level <= 0) {
            return null;
        }
        return $levels[$this->level]['name'];
    }

    /**
     * 颜色等级图
     * @return string|null
     */
    public function getLevelImageUrlAttribute()
    {
        $levels = SkinsLv::getList();
        if ($this->level <= 0) {
            return null;
        }
        return config('filesystems.disks.common.url') . '/' . $levels[$this->level]['bg_image'];
    }

    /**
     * 分母
     * @param $box_id
     * @return array
     */
    public static function setDenominator($box_id)
    {
        if (!isset(static::$denominator[$box_id])) {
            static::$denominator[$box_id] = static::where('box_id', $box_id)->sum('odds');
        }

        return static::$denominator[$box_id];
    }

    /**
     * 关联宝箱
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function box()
    {
        return $this->belongsTo(Box::class, 'box_id', 'id');
    }

    /**
     * 关联宝箱
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skins()
    {
        return $this->belongsTo(Skins::class, 'skin_id', 'id');
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
        static::deleting(function ($model) {
            //清理宝箱详情缓存
            Cache::delete(Box::$fields['cacheKey'][8] . $model->box_id);
            //清理用户爆率
            Redis::del(Box::$fields['cacheKey'][4] . $model->box_id);
            //清理主播爆率
            Redis::del(Box::$fields['cacheKey'][5] . $model->box_id);
            //清理幸运开箱爆率
            Redis::del(Box::$fields['cacheKey'][6] . $model->box_id);
            Redis::del(Box::$fields['cacheKey'][7] . $model->box_id);
            return true;
        });
    }
}
