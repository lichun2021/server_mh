<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameArenaBox extends Model
{
    /**
     * @var string 数据表
     */
    protected $table = 'game_arena_box';

    public static $fields = [
        0 => 'game_arena_lock_box_id_', //对战用户原子锁
        1 => 'game_arena_anchor_lock_box_id_', //对战主播原子锁
        2 => 'game_open_box_list_id_', //对战用户爆率
        3 => 'game_open_box_list_anchor_id_', //对战主播爆率
    ];

    /**
     * 关联奖品列表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function box_contain()
    {
        return $this->hasMany(BoxContain::class,'box_id','box_id');
    }

    /**
     * 关联箱子
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function box()
    {
        return $this->belongsTo(Box::class, 'box_id', 'id');
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
