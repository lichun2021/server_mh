<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LuckyBoxRecord
 * @package App
 * @author 春风 <860646000@qq.com>
 */
class LuckyBoxRecord extends Model
{
    public static $fields = [
        //外观
        'dura' => [
            0 => '无',
            1 => '崭新出厂',
            2 => '略有磨损',
            3 => '久经沙场',
            4 => '破损不堪',
            5 => '战痕累累',
            6 => '无涂装'
        ],
        //品质
        'lv' => [
            1 => '金',
            2 => '红',
            3 => '紫',
            4 => '蓝',
            5 => '灰'
        ]
    ];

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
     * <访问器> 获得外观名称
     *
     * @return mixed
     */
    public function getGetAwardDuraAliasAttribute()
    {
        return static::$fields['dura'][$this->get_award_dura];
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
     * <访问器> 获得品质名称
     *
     * @return string
     */
    public function getGetAwardLvAliasAttribute()
    {
        return static::$fields['lv'][$this->get_award_lv];
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
     * 关联目标饰品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function get_award()
    {
        return $this->belongsTo(Skins::class, 'get_award_id', 'id');
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
