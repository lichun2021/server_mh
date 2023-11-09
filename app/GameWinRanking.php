<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameWinRanking extends Model
{
    public $table = 'game_win_ranking';
    public $timestamps = false;

    public  static function write($user_id,$bean)
    {
        $date = date('Y-m-d');
        if (is_array($user_id)){
            foreach ($user_id as $userId){
                self::writeSave($userId, $bean, $date);
            }
        } else {
            self::writeSave($user_id, $bean, $date);
        }
    }

    private static function writeSave($user_id, $bean, $date)
    {
        $model = self::where('user_id', $user_id)
            ->where('date', $date)
            ->first();
        if ($model) {
            $model->increment('win', $bean);
        } else {
            $model = new self();
            $model->user_id = $user_id;
            $model->win = $bean;
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
