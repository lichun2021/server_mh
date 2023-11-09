<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRewardLog extends Model
{
    /**
     * @var array
     */
    public static $fields = [
        'type' => [
            1 => '邀请注册奖励',
            2 => '受邀充值奖励',
            3 => '首充奖励',
            4 => '累计充值返佣',
            5 => '解冻注册赠送金豆',
            6 => 'VIP充值奖励',
            7 => 'VIP升级红包',
            8 => '签到任务完成奖励',
            9 => '每日经典盲盒花费100T币任务',
            10 => '每日经典盲盒花费300T币任务',
            11 => '每日经典盲盒花费1000T币任务',
            12 => '每日经典盲盒花费5000T币任务',
            13 => '每日盲盒对战花费100T币任务',
            14 => '每日盲盒对战花费300T币任务',
            15 => '每日盲盒对战花费1000T币任务',
            16 => '每日盲盒对战花费5000T币任务',
            17 => '每日追梦花费100T币任务',
            18 => '每日追梦花费300T币任务',
            19 => '每日追梦花费1000T币任务',
            20 => '每日追梦花费5000T币任务'
        ]
    ];


    protected $hidden = [
        'msg', 'recharge_rebates_id',
    ];

    /**
     * <访问器> 奖励名称
     *
     * @return mixed
     */
    public function getTypeNameAttribute()
    {
        return self::$fields['type'][$this->type];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nextUser()
    {
        return $this->belongsTo(User::class, 'next_user_id', 'id');
    }

    /**
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
