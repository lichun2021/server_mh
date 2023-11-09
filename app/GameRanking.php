<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameRanking extends Model
{
    public $table = 'game_ranking';
    public $timestamps = false;

    public static function write($user_id, $bean)
    {
        $date = date('Y-m-d');
        $model = self::where('user_id', $user_id)
            ->where('date', $date)
            ->first();
        if ($model) {
            $model->increment('expend', $bean);
        } else {
            $model = new self();
            $model->user_id = $user_id;
            $model->expend = $bean;
            $model->date = $date;
            $model->save();
        }
    }

    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
