<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameArenaUser extends Model
{
    /**
     * 关联对战获奖列表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function game_award()
    {
        return $this->hasMany(GameAwardRecord::class,'user_id','user_id');
    }

    /**
     * 关联用户表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
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
