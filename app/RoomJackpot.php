<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RoomJackpot
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/9
 * Time：22:33
 */
class RoomJackpot extends Model
{
    /**
     * @var string
     */
    protected $table = 'room_jackpot';

    /**
     * 格式化时间
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
            RoomJackpotsList::where(['jackpot_id' => $model->id])->delete();
            return true;
        });
    }
}
