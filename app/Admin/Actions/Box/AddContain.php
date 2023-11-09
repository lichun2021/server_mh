<?php


namespace App\Admin\Actions\Box;

use Encore\Admin\Actions\RowAction;

class AddContain extends RowAction
{
    public $name = '新增奖项';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/box-contains/create?box_id=' . $this->getKey();
    }
}
