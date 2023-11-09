<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /**
     * @var array
     */
    public static $fields = [
        'type' => [
            1 => '文章',
            2 => '活动详情'
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
