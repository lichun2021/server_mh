<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/11/2
 * Time: 21:55
 */

namespace App\Admin\Actions\Room;

use Encore\Admin\Actions\RowAction;
class AwardList extends RowAction
{
    public $name = '装备列表';

    /**
     * @return  string
     */
    public function href()
    {
        return '/'.config('admin.route.prefix').'/room-award?room_id=' . $this->getKey();
    }
}
