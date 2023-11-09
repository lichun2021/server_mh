<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/13
 * Time: 19:46
 */

namespace App;

use App\Box;
use Illuminate\Database\Eloquent\Model;

class BoxLucky extends  Model
{
    /**
     * Table Name
     * @var string
     */
    protected $table = 'box_lucky';

    /**
     * 创建宝箱记录
     * @param \App\Box $box
     * @return BoxLucky
     */
    public static function createBoxRecord(Box $box)
    {
        $lucky_lucky = new self();
        $lucky_lucky->box_id = $box->id;
        $lucky_lucky->luck_value = $box->luck_interval;
        $lucky_lucky->luck_anchor_value = $box->luck_interval_anchor;
        $lucky_lucky->save();
        return $lucky_lucky;
    }

    /**
     * <修改器> 幸运值
     * @param $value
     * @throws \Exception
     */
    public function setLuckValueAttribute($value)
    {
        if (!is_numeric($value)){
            $luck_array = explode('/', $value);
            if (is_array($luck_array) && count($luck_array) === 2){
                $luck_rand_num = mt_rand($luck_array[0], $luck_array[1]);
                $this->attributes['luck_value'] = $luck_rand_num;
            } else {
                throw new \Exception('幸运记录LuckValue创建失败');
            }
        }
    }

    /**
     * <修改器> 主播幸运值
     * @param $value
     * @throws \Exception
     */
    public function setLuckAnchorValueAttribute($value)
    {
        if (!is_numeric($value)) {
            $luck_anchor_array = explode('/', $value);
            if (is_array($luck_anchor_array) && count($luck_anchor_array) === 2){
                $luck_anchor_num = mt_rand($luck_anchor_array[0], $luck_anchor_array[1]);
                $this->attributes['luck_anchor_value'] = $luck_anchor_num;
            } else {
                throw new \Exception('幸运记录(主播)LuckAnchorValue创建失败');
            }
        }
    }

    /**
     * 时间格式
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
