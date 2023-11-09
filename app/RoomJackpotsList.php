<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RoomJackpotsList
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/9
 * Time：22:32
 */
class RoomJackpotsList extends Model
{
    /**
     * @var string
     */
    protected $table = 'room_jackpots_list';

    /**
     * 关联奖池
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jackpot()
    {
        return $this->belongsTo(RoomJackpot::class,'jackpot_id','id');
    }

    /**
     * 关联饰品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skins()
    {
        return $this->belongsTo(Skins::class,'skin_id','id');
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
