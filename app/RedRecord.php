<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RedRecord extends Model
{
    public static $fields = [
      'type' => [
          1 => '红包活动',
          2 => '口令红包'
      ]
    ];

    /**
     * 关联红包
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function red()
    {
        return $this->belongsTo(Red::class, 'red_id', 'id');
    }

    /**
     * 关联用户
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
