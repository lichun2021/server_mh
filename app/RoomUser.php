<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class RoomUser extends Model
{
    protected $table = 'room_users';

    protected $appends = [];

    protected $fillable = ['user_id', 'room_id'];
    /**
     * 字段映射
     *
     * @var array
     */
    public static $fields = [
        'cacheKey' => 'room_id_user_list_'
    ];

    /**
     * 房间用户列表
     * @param $room_id
     * @return array
     */
    public static function roomUserList($room_id)
    {
        $key = self::$fields['cacheKey'].$room_id;
        $users = Cache::get($key);
        if ($users === null){
            $userIds = RoomUser::query()->where('room_id',$room_id)->pluck('user_id')->toArray();
            $usersRes = User::query()->whereIn('id',$userIds)->pluck('name','id')->toArray();
            $users = [0 => '无'];
            foreach ($usersRes as $key => $val){
                $users[$key] = $val;
            }
            Cache::put($key,$users,60);
        }
        return $users;
    }

    /**
     * 用户关系
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
