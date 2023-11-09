<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameArenaBot extends Model
{

    /**
     * @var string
     */
    protected $table = 'game_arena_bot';

    /**
     * @var string[]
     */
    protected $casts = [
        'boxs' => 'json',
    ];

    /**
     * @var array
     */
    public static $fields = [
        'check_status' => [
            0 => '失效',
            1 => '正常'
        ]
    ];

    /**
     * @param $value
     * @return array
     */
    public function getBoxsAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    /**
     * @param $value
     */
    public function setBoxsAttribute($value)
    {
        $this->attributes['boxs'] = json_encode(array_values($value));
    }

    /**
     * @return array
     */
    public static function getBoxList()
    {
        $skins = Box::select(['id', 'name', 'game_bean'])
            ->where(['is_game' => 1])
            ->get()
            ->toArray();
        $data = [];
        foreach ($skins as $item){
            $data[$item['id']] = $item['name'].' - '.$item['game_bean'];
        }
        return $data;
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
