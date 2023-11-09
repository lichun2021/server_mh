<?php

namespace App\Admin\Actions\Room;

use Encore\Admin\Actions\RowAction;

/**
 * Class JackpotAddAward
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/9
 * Time：22:33
 */
class JackpotAddAward extends RowAction
{
    public $name = '新增奖项';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/room-jackpots-list/create?jackpot_id=' . $this->getKey();
    }
}
