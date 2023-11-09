<?php

namespace App\Admin\Controllers;

use App\Bean;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class BeanController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Bean());
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->column('id', __('Id'));
        $grid->column('price', '价格');
        $grid->column('bean',getConfig('bean_name'));
        $grid->column('card_link','卡密链接')->editable();
        $grid->column('product_id', '支付商品ID')->editable();
        $grid->column('is_putaway', '是否上架')->editable('select', Bean::$fields['is_putaway']);

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Bean());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`删除`按钮
            $tools->disableDelete();
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
            $footer->disableViewCheck();

        });

        $form->decimal('price', '价格')->default(0.00);
        $form->currency('bean', getConfig('bean_name'))->symbol('$');
        $form->url('card_link', '卡密链接');
        $form->number('product_id', '支付商品ID');
        $form->switch('is_putaway', '是否上架')->default(1);
        //保存后执行
        $form->saved(function (Form $form) {
            //清除等级缓存
            Cache::delete(Bean::$fields['cacheKey']);
        });
        return $form;
    }
}
