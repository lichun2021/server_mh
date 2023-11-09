<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BeanRecord extends Model
{

    /**
     * 字段映射
     *
     * @var array
     */
    public static $fields = [
        'status' => [
            0 => '未付款',
            1 => '已付款',
        ]
    ];

    public function getStatusAliasAttribute()
    {
        return static::$fields['status'][$this->status];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

}
