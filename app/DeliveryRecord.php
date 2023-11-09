<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class DeliveryRecord extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function record()
    {
        return $this->belongsTo(BoxRecord::class, 'record_id', 'id');
    }
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
