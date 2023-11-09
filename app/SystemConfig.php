<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    /**
     * @var bool 关闭时间更新
     */
    public $timestamps = false;
    
    public function getValueAttribute($value)
    {
        if ($this->type === 'file'){
            return config('filesystems.disks.common.url') .'/'. $value;
        }
        return $value;
    }
}
