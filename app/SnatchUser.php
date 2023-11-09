<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SnatchUser extends Model
{
    /**
     * 关联参与用户信息
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user_info()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
