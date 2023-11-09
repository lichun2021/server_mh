<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailCode extends Model
{
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
