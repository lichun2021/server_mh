<?php


namespace App\Admin\Actions\Delivery;

use Encore\Admin\Actions\RowAction;
class OpenSteam extends RowAction
{
    public $name = '手动发货';

    /**
     * @return  string
     */
    public function href()
    {
        return "https://www.baidu.com/?box_id=" . $this->getKey();
    }
}
