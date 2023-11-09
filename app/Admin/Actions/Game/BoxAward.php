<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/11/17 16:32
 */

namespace App\Admin\Actions\Game;

use Encore\Admin\Actions\RowAction;
class BoxAward extends RowAction
{
    public $name = '查看奖项';
    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/game-awards?box_id=' . $this->getKey();
    }
}
