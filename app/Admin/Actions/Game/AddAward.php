<?php


namespace App\Admin\Actions\Game;

use Encore\Admin\Actions\RowAction;

/**
 * Class BoxContains
 * @package App\Admin\Actions\Game
 * @author 春风 <860646000@qq.com>
 */
class AddAward extends RowAction
{
    public $name = '新增奖项';
    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/game-awards/create?box_id=' . $this->getKey();
    }
}
