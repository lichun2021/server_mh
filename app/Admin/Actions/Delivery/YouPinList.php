<?php

namespace App\Admin\Actions\Delivery;

use Encore\Admin\Actions\RowAction;
class YouPinList extends RowAction
{
    public $name = '有品在售';

    public function href()
    {
        return '/'.config('admin.route.prefix').'/youpin?record_id=' . $this->getKey();
    }
}
