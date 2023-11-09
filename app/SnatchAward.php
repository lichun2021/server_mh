<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SnatchAward extends Model
{

    /**
     * 关联获奖用户信息
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'get_user_id', 'id');
    }

    /**
     * 关联奖品信息
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skins_info()
    {
        return $this->belongsTo(Skins::class, 'box_award_id', 'id');
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
