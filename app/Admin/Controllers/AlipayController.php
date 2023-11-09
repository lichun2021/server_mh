<?php

namespace App\Admin\Controllers;

use App\Alipay;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class AlipayController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '支付宝列表';

    /**
     * @var array[]
     */
    protected static $states = [
        'on' => ['value' => 1, 'text' => '打开', 'color' => 'success'],
        'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Alipay());
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //查询过滤
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/2, function ($filter) {
                $filter->like('account', '支付宝账号');
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('app_id', 'App Id');
            });
        });
        //禁用制定操作
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->column('id', 'Id');
        $grid->column('account', '支付宝账号');
        $grid->column('app_id', 'App Id');
        $grid->column('return_url', '同步通知')->editable();
        $grid->column('notify_url', '异步通知')->editable();
        $grid->column('status', '状态')->switch(self::$states);
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Alipay());

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        $form->text('account', '支付宝账号')->rules(['required', 'unique:alipay,account,{{id}}'], [
            'required' => '请输入支付宝账号',
            'unique' => '支付宝账号已存在',
        ])->help('支付宝登录账号');
        $form->text('app_id', 'App id')->rules(['required', 'unique:alipay,app_id,{{id}}'], [
            'required' => '请输入支付宝应用AppId',
            'unique' => '支付宝应用AppId已存在',
        ])->help('支付宝开放平台应用AppId');
        $form->password('private_key', '应用私钥')->rules(['required'], [
            'required' => '请输入应用私钥'
        ])->help('应用私钥');
        $form->password('alipay_public_key', '支付宝公钥')->rules(['required'], [
            'required' => '请输入支付宝公钥'
        ])->help('支付宝公钥');
        $form->text('encrypt_key', 'AES密钥')
            ->help('接口内容加密方式AES密钥');
        $form->text('return_url', '同步通知');
        $form->text('notify_url', '异步通知')->rules(['required'], [
            'required' => '请输入支付宝异步通知接收url'
        ])->help('支付宝异步通知接收url');
        $form->switch('status', '状态')
            ->states(self::$states)
            ->help('账号启用状态');

        return $form;
    }
}
