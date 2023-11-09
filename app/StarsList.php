<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StarsList
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/29
 * Time：1:55
 */
class StarsList extends Model
{
    protected $table = 'stars_list';

    public static $fields = [
        'status' => [
            0 => '禁用',
            1 => '正常'
        ],
        'cacheKey' => [
            0 => 'star_wars_key_',
            1 => 'star_wars_Jackpot_key_id_'
        ]
    ];

    /**
     * @param $value
     * @return string
     */
    public function getCoverAttribute($value)
    {
        return config('filesystems.disks.common.url') . '/' . $value;
    }

    /**
     * 包含物品
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contains()
    {
        return $this->hasMany(StarsContain::class, 'stars_id', 'id');
    }

    /**
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model) {
            $isUse = StarsContain::where(['stars_id' => $model->id])->exists();
            if ($isUse) {
                throw new \Exception('星星内含饰品，无法删除！');
            }
            return true;
        });
    }

    /**
     * 时间格式化
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
