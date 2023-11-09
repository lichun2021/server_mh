<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BaiduChannel extends Model
{

    /**
     * 获得渠道
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function getChannel()
    {
        $origin = request()->header('origin');

        if (empty($origin)){
            return null;
        }

        $originInfo = parse_url($origin);
        $host = $originInfo['host'];

        $channel = self::query()->where(['domain_name' => $host, 'status' => 1])->first();

        if (!$channel){
            return null;
        }
        return $channel;
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
