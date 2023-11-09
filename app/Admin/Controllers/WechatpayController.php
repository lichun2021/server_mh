<?php

namespace App\Admin\Controllers;

use App\Wechatpay;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class WechatpayController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '微信支付';

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
        $grid = new Grid(new Wechatpay());

        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用制定操作
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        //查询过滤
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/2, function ($filter) {
                $filter->like('merchant_id', '商户号');
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('app_id', 'App Id');
            });
        });

        $grid->column('id', 'Id');
        $grid->column('merchant_id', '商户号');
        $grid->column('app_id', 'AppId');
        $grid->column('notify_url', '异步通知');
        $grid->column('status', '状态')->switch(self::$states);
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
        $form = new Form(new Wechatpay());

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        $form->text('merchant_id', '商户号')->rules(['required', 'unique:wechatpay,merchant_id,{{id}}'], [
            'required' => '请输入微信商户号',
            'unique' => '微信商户号已存在',
        ])->help('微信支付商户号');
        $form->text('app_id', 'AppId')->rules(['required'], [
            'required' => '请输入AppId',
        ])->help('请输入AppId');
        $form->file('private_key', '商户私钥')->disk('cert')
            ->move('wechatpay')
            ->uniqueName()
            ->required()
            ->help('微信支付商户私钥“apiclient_key.pem”');
        $form->text('merchant_certificate_serial', '商户证书序列号')->rules(['required','size:40'], [
            'required' => '请输入商户证书序列号',
            'size' => '商户证书序列号应为40个字符'
        ])->help('请输入40字符的商户证书序列号');
        $form->text('api_key', 'Api Key')->rules(['required','size:32'], [
            'required' => '请输入Api Key',
            'size' => 'Api Key应为32个字符'
        ])->default('RAlTRo0JvEFdsgerhJznBNA3LZUiQIP1')
            ->disable()
            ->help('请输入32字符的Api Key, 微信内必须设置此值为Api V3 Key');
        $form->text('notify_url', '异步通知')->rules(['required', 'url'], [
            'required' => '请输入异步通知Url',
            'url' => '异步通知格式错误，不是标准的Url。'
        ])->help('请输入异步通知Url');
        $form->switch('status', __('Status'))
            ->states(self::$states)
            ->default(1)
            ->help('账号状态');

        return $form;
    }
}
