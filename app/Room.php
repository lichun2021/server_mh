<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    /**
     * @var array 隐藏房间密码字段
     */
    //protected $hidden = ['password'];

    /**
     * @var string 数据表
     */
    protected $table = 'rooms';

    /**
     * 字段映射
     *
     * @var array
     */
    public static $fields = [
        'status' => [
            -1 => '待上架',
            0 => '进行中',
            1 => '已结束'
        ],
        'type' => [
            0 => '官方房间',
            1 => '主播房间'
        ],
        'cacheKey' => [
            0 => 'room_list',
            1 => 'room_detail'
        ]
    ];

    /**
     * <访问器> 参与人数
     *
     * @return int
     */
    public function getJoinNumberAttribute()
    {
        return RoomUser::where('room_id', $this->id)->count();
    }

    /**
     * <访问器> 是否是密码房间
     *
     * @return int
     */
    public function getIsPwdAttribute()
    {
        $is_pwd = empty($this->password) ? 0:1;
        return $is_pwd;
    }

    /**
     * <访问器> 奖品总价值（金豆）
     *
     * @return string
     */
    public function getAwardBeanAttribute()
    {
        return BoxRecord::whereIn('id', function ($query) {
            $query->select('box_record_id')->from('room_awards')->where('room_id', $this->id);
        })->sum('bean');
    }

    /**
     * <访问器> 状态别名
     *
     * @return string
     */
    public function getStatusAliasAttribute()
    {
        return static::$fields['status'][$this->status];
    }

    public function users()
    {
        return $this->hasMany(RoomUser::class,'room_id','id');
    }

    /**
     * <访问器> 奖品数量
     * @return int
     */
    public function getAwardsCountAttribute()
    {
        return $this->awards()->count('id');
    }

    /**
     * 关联奖品列表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function awards()
    {
        return $this->hasMany(RoomAward::class,'room_id','id');
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
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model){
            //删除时删除用户记录
            RoomUser::where('room_id',$model->id)->delete();
            $awards = RoomAward::where('room_id',$model->id)->get();

            //删除房间时如果未开奖奖品还给房主
            $box_record_ids = [];
            foreach ($awards as $award){
                if ($award->get_user_id == 0 && $model->status == 0){
                    $box_record_ids[] = $award->box_record_id;
                }
                //删除记录
                $award->delete();
            }
            BoxRecord::whereIn('id', $box_record_ids)->update(['status' => 0]);
            //删除参与记录
            RoomRecord::where('room_id',$model->id)->delete();
        });
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
