<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Red extends Model
{

    /**
     * @var array
     */
    protected $casts = [
        'percentage' => 'array'
    ];

    /**
     * @var array
     */
    public static $fields = [
        'status' => [
            0 => '未开始',
            1 => '进行中',
            2 => '已抢完',
            3 => '已结束'
        ]
    ];

    /**
     * 增加字段
     * @var array
     */
    protected $appends = ['status', 'remainder'];

    /**
     * 状态
     * @return int
     */
    public function getStatusAttribute()
    {
        $status = 0;
        switch (true) {
            case now() < $this->start_time:
                //未开始
                $status = 0;
                break;
            case now() > $this->start_time && now() < $this->end_time:
                //已开始
                $red_record = RedRecord::where(['red_id' => $this->id, 'type' => 1])->count('id');
                if ($this->num <= $red_record){
                    $status = 2;
                } else {
                    $status = 1;
                }
                break;
            case now() > $this->end_time:
                //已结束
                $status = 3;
                break;
        }
        return $status;
    }

    /**
     * <访问器> 红包区间值
     * @param $value
     * @return string
     * @throws \Exception
     */
    /*public function getPercentageAttribute($value)
    {
        $value = json_decode($value,true);
      
        if (is_array($value) && count($value) == 2){
            return $value[0].'/'.$value[1];
        }
         \Log::info('记录'.$value);
        throw new \Exception('红包区间设置有误！');
    }*/

    /**
     * 剩余
     * @return mixed
     */
    public function getRemainderAttribute()
    {
        $in = RedRecord::where(['red_id' => $this->id, 'type' => 1])->count('id');
        return $this->num - $in;
    }

    /**
     * 格式化时间
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
