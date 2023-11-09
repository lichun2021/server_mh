<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Wechatpay extends Model
{
    public $table = 'wechatpay';

    /**
     * 字段映射
     * @var \string[][]
     */
    public static $fields = [
        'status' => [
            0 => '未启用',
            1 => '已启用'
        ]
    ];

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
