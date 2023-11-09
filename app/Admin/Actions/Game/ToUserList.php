<?php


namespace App\Admin\Actions\Game;

use Encore\Admin\Actions\RowAction;
class ToUserList extends RowAction
{
    public $name = '参与玩家';
    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/game-arena-users?game_arena_id=' . $this->getKey();
    }
}
