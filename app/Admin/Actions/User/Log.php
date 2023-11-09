<?php


namespace App\Admin\Actions\User;

use Encore\Admin\Actions\RowAction;
class Log extends RowAction
{
    public $name = 'Ip记录';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/login-ip-logs?user_id=' . $this->getKey();
    }
}
