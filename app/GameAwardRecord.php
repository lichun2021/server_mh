<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameAwardRecord extends Model
{

    /**
     * 关联饰品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skins()
    {
        return $this->belongsTo(Skins::class,'award_id','id');
    }

    /**
     * 关联宝箱
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function box()
    {
        return $this->belongsTo(Box::class,'box_id','id');
    }

    /**
     * 关联用户
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
