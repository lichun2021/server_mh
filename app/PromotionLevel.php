<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromotionLevel extends Model
{
    /**
     * @var bool 关闭时间更新
     */
    public $timestamps = false;

    /**
     * 获取推广等级数组列表
     *
     * @return array
     */
    public static function getList()
    {
        return PromotionLevel::query()->get()->toArray();
    }

    /**
     * 按推广等级获取相应奖励
     * @param $level integer
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function getOne($level)
    {
        return PromotionLevel::query()->where('level',$level)->first();
    }
}
