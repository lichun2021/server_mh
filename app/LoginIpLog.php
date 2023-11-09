<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoginIpLog extends Model
{
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
