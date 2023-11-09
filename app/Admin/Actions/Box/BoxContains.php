<?php


namespace App\Admin\Actions\Box;

use Encore\Admin\Actions\RowAction;

class BoxContains extends RowAction
{
    public $name = '查看奖项';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/box-contains?box_id=' . $this->getKey();
    }
}
