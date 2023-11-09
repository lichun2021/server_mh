<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bean extends Model
{
    protected $table = 'beans';

    /**
     * 字段映射
     *
     * @var array
     */
    public static $fields = [
        'is_putaway' => [
            0 => '下架',
            1 => '上架'
        ],
        'cacheKey' => 'beans_list'
    ];

    /**
     * 福利宝箱条件
     * @return array
     */
    public static function getWelfareList()
    {
        $model = self::query()->select(['id','bean'])->orderBy('bean')->get()->toArray();
        $list = [];
        foreach ($model as $item){
            $list[$item['bean']] = '$'.$item['bean'];
        }
        return $list;
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
