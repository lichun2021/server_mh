<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    /**
     * @var string
     */
    public $table = 'sms_code';

    /**
     * @var string[]
     */
    protected $casts = [
        'request' => 'json',
        'response' => 'json'
    ];

    /**
     * 格式化时间
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
