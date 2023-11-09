<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/30
 * Time: 22:40
 */

namespace App\Admin\Actions\Delivery;

use Encore\Admin\Actions\RowAction;
class ZbtList extends RowAction
{
    public $name = 'ZBT在售查询';

    public function href()
    {
        return '/'.config('admin.route.prefix').'/zbt?record_id=' . $this->getKey();
    }
}
