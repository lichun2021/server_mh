<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomAward extends Model
{
    protected $table = 'room_awards';

    /**
     * 指定用户关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function designated()
    {
        return $this->belongsTo(User::class, 'designated_user', 'id');
    }

    /**
     * 仓库关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function box_record()
    {
        return $this->belongsTo(BoxRecord::class, 'box_record_id', 'id');
    }

    /**
     * 用户关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'get_user_id', 'id');
    }

    /**
     * 房间关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
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
