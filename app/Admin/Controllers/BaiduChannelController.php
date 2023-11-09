<?php

namespace App\Admin\Controllers;


use App\User;
use App\BeanRecord;
use App\BaiduChannel;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\AdminController;

class BaiduChannelController extends AdminController
{
    private static $states = [
        'on' => ['value' => 1, 'text' => '打开', 'color' => 'success'],
        'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
    ];

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '渠道管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BaiduChannel());

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->column(1 / 2, function ($filter) {
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->like('name', '名称');
            });
        });
        $grid->export(function ($export) {
            $export->column('status', function ($value, $original) {
                if ($original === 0){
                    return '关闭';
                }
                return '打开';
            });
        });

        $grid->model()->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('name', '名称');
        $grid->column('domain_name', '域名');
        $grid->column('注册用户')->display(function (){
            return User::where(['baidu_channel_id' => $this->id])->count('id');
        });
        $grid->column('付费用户')->display(function (){
            return User::where(['baidu_channel_id' => $this->id, 'is_recharge' => 1])->count('id');
        });;
        $grid->column('总充值')->display(function (){
            $id = $this->id;
            return BeanRecord::query()->where('status',1)->whereHas('user', function ($query) use ($id) {
                $query->where(['baidu_channel_id' => $id]);
            })->sum('bean');
        });
        $grid->column('status', __('Status'))->switch(self::$states);
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BaiduChannel());
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });
        $form->text('name', '名称')->rules([
            'required',
            'min:1',
            'max:8',
            'unique:baidu_channels,name,{{id}}'
        ], [
            'required' => '请输入渠道名称',
            'min' => '渠道名称最少输入1个字符',
            'max' => '渠道名称最多支持输入8个字符',
            'unique' => '渠道名称已存在'
        ])->help('渠道名称 1-8 个字符');
        $form->text('domain_name', '域名')->rules([
            'required',
            'unique:baidu_channels,domain_name,{{id}}'
        ], [
            'required' => '请输入域名',
            'unique' => '域名已存在',
        ])->help('输入域名Host部分 示例：推广网址 https://db01.mucsgo.com  只需要输入 db01.mucsgo.com ');
        $form->text('token', 'Token')->help('百度渠道Token');
        $form->switch('status', __('Status'))->states(self::$states)->default(1);

        return $form;
    }
}
