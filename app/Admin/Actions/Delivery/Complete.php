<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/11/8
 * Time: 0:48
 */

namespace App\Admin\Actions\Delivery;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Actions\RowAction;

class Complete extends RowAction
{
    public $name = '提货完成';

    public function handle(Model $model)
    {
        $model->status = 1;
        $model->save();
        return $this->response()->success('操作成功')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定设为'. $this->name . '？','直接设为提货完成,ZBT不会产生真实交易,不会扣除T币。');
    }
}