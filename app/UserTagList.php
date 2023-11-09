<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTagList extends Model
{
    public $table = 'user_tag_list';

    public $timestamps = false;

    protected static function booted()
    {
        static::deleted(function ($model) {
            UserTag::where('tag_id',$model->id)->delete();
        });
    }
}
