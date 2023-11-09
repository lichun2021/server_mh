<?php


namespace App\Admin\Controllers;

use App\BoxRecord;
use App\Skins;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;
use Encore\Admin\Form;
use Encore\Admin\Controllers\AdminController;
class SendSkinsController extends AdminController
{
    protected $title = '奖品等级';

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BoxRecord());
        $user_id = request()->get('user_id');
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
        });
        //
        $form->hidden('get_user_id')->default($user_id);
        $form->hidden('user_id')->default($user_id);
        $form->hidden('box_name')->default('饰品发放');
        $form->hidden('box_bean')->default(0);
        $form->hidden('code')->default(getUniqueOrderNumber());
        $form->hidden('uuid')->default(Str::uuid());
        $form->hidden('status')->default(0);
        $form->select('skin_id', '饰品')->options(Skins::getList())->required();
        $form->hidden('name')->default(Str::uuid());
        $form->hidden('cover')->default(0);
        $form->hidden('dura')->default(Str::uuid());
        $form->hidden('lv')->default(0);
        $form->hidden('bean')->default(0);
        //保存前回调
        $form->saving(function (Form $form) {
            $skins = Skins::find($form->skin_id);
            if (!$skins){
                throw new \Exception('数据有误');
            }
            $form->name = $skins->name;
            $form->cover = $skins->getRawOriginal('cover');
            $form->dura = $skins->dura;
            $form->lv = $skins->lv;
            $form->bean = $skins->bean;
        });
        $form->saved(function (Form $form) {
            return redirect('/'.config('admin.route.prefix').'/box-records?user_id=' . $form->user_id);
        });
        return $form;
    }
}
