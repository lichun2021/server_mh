<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StarsContain extends Model
{
    public static $seat;
    /**
     * 关联饰品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stars()
    {
        return $this->belongsTo(StarsList::class,'stars_id','id');
    }

    /**
     * 关联饰品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skins()
    {
        return $this->belongsTo(Skins::class,'skin_id','id');
    }
    
    /**
     * 爆率显示
     * @return string
     */
    public function getOddsPercentAttribute()
    {
        $a = 'a' . self::$seat;
        if (!isset(self::$seat)) {
            return '0%';
        }
        return bcdiv(self::query()->find($this->id)->$a * 100, self::setDenominator($this->stars_id, $a), 2) . '%';
    }

    /**
     * 分母
     * @param $stars_id
     * @return array
     */
    private static function setDenominator($stars_id, $a)
    {
        return static::where('stars_id', $stars_id)->sum($a);
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
