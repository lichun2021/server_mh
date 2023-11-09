<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Vip
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/6/9
 * Time：17:11
 */
class Vip extends Model
{
    /**
     * @var string 数据表名
     */
    public $table = 'vip';

    /**
     * @var string[] vip等级
     */
    public static  $levelMap = [
        1 => 'VIP1',
        2 => 'VIP2',
        3 => 'VIP3',
        4 => 'VIP4',
        5 => 'VIP5',
        6 => 'VIP6',
        7 => 'VIP7',
        8 => 'VIP8',
        9 => 'VIP9',
        10 => 'VIP10',
        11 => 'VIP11',
        12 => 'VIP12',
    ];

    /**
     * 获取VIP等级列表
     * @return mixed
     */
    public static function getList()
    {
        return self::get()->toArray();
    }

    /**
     * VIP等级别名
     * @return string
     */
    public function getLevelAliasAttribute()
    {
        return self::$levelMap[$this->level];
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
