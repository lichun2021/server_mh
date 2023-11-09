<?php

namespace App\Admin\Actions\Room;

use Encore\Admin\Actions\RowAction;

/**
 * Class JackpotAwards
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/9
 * Time：22:33
 */
class JackpotAwards extends RowAction
{
    public $name = '查看奖项';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/room-jackpots-list?jackpot_id=' . $this->getKey();
    }
}
