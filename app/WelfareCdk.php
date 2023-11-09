<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WelfareCdk extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $table = 'welfare_cdk';

    /**
     * @var array
     */
    public static $fields = [
        'status' => [
            0 => '未使用',
            1 => '已使用'
        ]
    ];

    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联活动
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function welfare()
    {
        return $this->belongsTo(Welfare::class,'welfare_id','id');
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
