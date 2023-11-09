<?php
/**
 * Steam基础信息表
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class SteamItem extends Model
{
    protected $table = 'steam_items';

    /**
     * 字段映射
     *
     * @var array
     */
    public static $fields = [
        'is_putaway' => [
            0 => '下架',
            1 => '上架'
        ]
    ];

    /**
     * <访问器> 封面图
     *
     * @param [type] $value
     * @return string
     */
    public function getCoverAttribute($value)
    {
        return $value ? config('filesystems.disks.common.url') . '/' . $value : '';
    }

    public function boxAwards()
    {
        return $this->hasMany(BoxAward::class, 'box_id', 'id');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
