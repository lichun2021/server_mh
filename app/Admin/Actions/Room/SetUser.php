<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/11/2
 * Time: 22:00
 */

namespace App\Admin\Actions\Room;

use App\RoomUser;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class SetUser extends RowAction
{
    public $name = '指定获奖者';

    public function handle(Model $model)
    {
        $model->designated_user = request('designated_user');
        $model->save();

        return $this->response()->success('指定获奖者成功！')->refresh();
    }

    public function form()
    {
        $this->select('designated_user', '用户')->options(RoomUser::roomUserList(request()->get('room_id')));
    }
}
