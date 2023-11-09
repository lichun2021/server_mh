<?php

namespace App\Admin\Actions\Room;

use Encore\Admin\Actions\RowAction;

class UserList extends RowAction
{
    public $name = '用户列表';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/room-users?room_id=' . $this->getKey();
    }
}
