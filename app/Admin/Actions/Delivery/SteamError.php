<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/11/8
 * Time: 19:45
 */

namespace App\Admin\Actions\Delivery;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Actions\RowAction;

class SteamError extends RowAction
{
    public $name = 'Steam错误';

    public function handle(Model $model)
    {
        $model->back_message = 'Steam 错误';
        $model->save();
        return $this->response()->success('操作成功')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定设为'. $this->name . '？','不会退回玩家仓库，用于后台数据修正标注。');
    }
}