<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SyntheRecord extends Model
{
    public static $fields = [
        'status' => [
            0 => '合成失败',
            1 => '合成成功'
        ]
    ];

    /**
     * <访问器> 合成状态
     *
     * @return mixed
     */
    public function getStatusAliasAttribute()
    {
        return static::$fields['status'][$this->status];
    }

    /**
     * <访问器> 目标外观名称
     *
     * @return mixed
     */
    public function getAwardDuraAliasAttribute()
    {
        return static::$fields['dura'][$this->award_dura];
    }

    /**
     * <访问器> 目标品质名称
     *
     * @return string
     */
    public function getAwardLvAliasAttribute()
    {
        return static::$fields['lv'][$this->award_lv];
    }

    /**
     * 关联目标饰品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function award()
    {
        return $this->belongsTo(Skins::class, 'award_id', 'id');
    }

    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
