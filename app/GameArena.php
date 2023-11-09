<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameArena extends Model
{

    /**
     * 属性转换
     * @var array
     */
    protected $casts = [
        'win_user_id' => 'array',
    ];

    /**
     * 枚举
     * @var array
     */
    public static $fields = [
        //对战状态
        'status' => [
            0 => '等待中',
            1 => '进行中',
            2 => '已结束'
        ],
        //对战模式
        'user_num' => [
            2 => '双人对战',
            3 => '三人对战',
            4 => '四人对战'
        ]
    ];

    /**
     * 默认追加
     * @var array
     */
    protected $appends = ['status_alias'];

    /**
     * 查询对战玩家
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function game_arena_player()
    {
        return $this->hasMany(GameArenaUser::class, 'game_arena_id', 'id');
    }

    /**
     * 查询对战箱子
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function game_arena_box()
    {
        return $this->hasMany(GameArenaBox::class, 'game_arena_id', 'id');
    }

    /**
     * 关联创建用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function create_user()
    {
        return $this->belongsTo(User::class,'create_user_id','id');
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
}
