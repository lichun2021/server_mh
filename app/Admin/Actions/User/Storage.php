<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/11/7 16:12
 */

namespace App\Admin\Actions\User;

use Encore\Admin\Actions\RowAction;
class Storage extends RowAction
{
    public $name = '仓库';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/box-records?user_id=' . $this->getKey();
    }
}
