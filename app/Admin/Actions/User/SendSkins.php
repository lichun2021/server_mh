<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2021/9/8
 * Time: 20:41
 */

namespace App\Admin\Actions\User;

use App\BoxRecord;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SendSkins
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/10
 * Time：1:17
 */
class SendSkins extends RowAction
{
    public $name = '饰品发放';

    public function handle(Model $model)
    {
        $user_id = request()->get('user_id');
        $num = request()->get('num');
        for ($i = 0;$i < $num;$i++){
            $box_record = new BoxRecord();

            $box_record->get_user_id = $user_id;
            $box_record->user_id = $user_id;
            //$box_record->box_id = $box->id;
            $box_record->box_name = '饰品发放';
            //$box_record->box_bean = $box->bean;
            $box_record->skin_id = $model->id;
            $box_record->name = $model->name;
            $box_record->cover = $model->getRawOriginal('cover');
            $box_record->dura = $model->dura;
            $box_record->bean = $model->bean;
            $box_record->code = getUniqueOrderNumber();
            $box_record->type = 0;
            $box_record->is_purse = $model->is_purse;
            $box_record->save();
        }
        return $this->response()->success('饰品发放成功！')->refresh();
    }

    public function form()
    {
        $this->select('user_id', '用户')
            ->rules('required',[
                'required' => '请选择用户'
            ])->ajax('/'.config('admin.route.prefix').'/api/users');
        $this->integer('num','数量')->rules('required|min:1',[
            'required' => '请输入发放数量',
            'min' => '发放数量不能小于1'
        ]);
    }
}
