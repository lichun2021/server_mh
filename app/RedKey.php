<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RedKey extends Model
{
    /**
     * @var array
     */
    protected $casts = [
        'denomination' => 'array'
    ];

    /**
     * 时间格式化
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 关联用户表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
