<?php
/**
 * 房间记录表
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
/**
 * 饰品
 * Class RoomRecord
 * @package App
 * @author 春风 <860646000@qq.com>
 */
class RoomRecord extends Model
{
    protected $table = 'room_records';

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
