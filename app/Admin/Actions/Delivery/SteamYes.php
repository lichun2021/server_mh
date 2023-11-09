<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/11/8
 * Time: 19:59
 */

namespace App\Admin\Actions\Delivery;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Actions\RowAction;
class SteamYes extends RowAction
{
    public $name = 'Steam OK';

    public function handle(Model $model)
    {
        $model->back_message = '';
        $model->save();
        return $this->response()->success('操作成功')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定设为Steam数据已处理完成状态');
    }
}