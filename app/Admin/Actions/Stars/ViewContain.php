<?php

namespace App\Admin\Actions\Stars;

use Encore\Admin\Actions\RowAction;
class ViewContain extends RowAction
{
    public $name = '查看奖项';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/stars-contain?stars_id=' . $this->getKey();
    }
}
