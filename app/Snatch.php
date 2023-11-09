<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Snatch extends Model
{
    /**
     * @var string 表名
     */
    protected $table = 'snatchs';

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = ['award_id', 'win_user_id'];

    /**
     * 默认追加
     * @var array
     */
    protected $appends = ['status_alias'];

    /**
     * 枚举
     * @var array
     */
    public static $fields = [
        //对战状态
        'status' => [
            0 => '等待中',
            1 => '已开奖'
        ]
    ];

    /**
     * 关联玩家
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function snatch_players()
    {
        return $this->hasMany(SnatchUser::class, 'snatch_id', 'id');
    }

    /**
     * 关联奖品列表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function award()
    {
        return $this->belongsTo(SnatchAward::class,  'id','snatch_id');
    }

    /**
     * 关联获胜用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function win_user()
    {
        return $this->belongsTo(User::class, 'win_user_id', 'id');
    }

    /**
     * 访问器 状态别名
     * @return mixed
     */
    public function getStatusAliasAttribute()
    {
        return static::$fields['status'][$this->status];
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

    /**
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model){
            //删除夺宝用户
            SnatchUser::query()->where(['snatch_id' => $model->id])->delete();
            //删除夺宝奖品
            SnatchAward::query()->where(['snatch_id' => $model->id])->delete();
            return true;
        });
    }
}
