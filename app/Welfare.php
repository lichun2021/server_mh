<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Welfare extends Model
{
    protected $appends = ['type_alias'];

    public static $fields = [
        'type' => [
            1 => '每日福利',
            2 => '累计充值福利',
            3 => '充值福利',
            4 => 'CDK箱子',
        ],
        'cacheKey' => [
            0 => 'welfare_lock_box_id_',
            1 => 'welfare_anchor_lock_box_id_',
            2 => 'welfare_box_id_',
            3 => 'welfare_anchor_box_id_',
        ]
    ];

    /**
     * Type 别名
     * @return mixed
     */
    public function getTypeAliasAttribute()
    {
        return static::$fields['type'][$this->type];
    }

    /**
     * 关联推广等级
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function promotion()
    {
        return $this->belongsTo(PromotionLevel::class, 'promotion_level', 'level');
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
     * 格式化时间
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
