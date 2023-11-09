<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    /**
     * @var array
     */
    public static $fields = [
        'status' => [
            0 => '关闭',
            1 => '打开'
        ],
        'cacheKey' => 'banner_list'
    ];

    public function getImageAttribute($value)
    {
        return config('filesystems.disks.common.url') . '/' . $value;
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
