<?php

namespace App\Admin\Controllers;

use App\RechargeRebate;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RechargeRebateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '充值累积返利';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RechargeRebate());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用分页
        $grid->disablePagination();
        //禁用查询过滤器
        $grid->disableFilter();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();

        $grid->column('id', __('Id'));
        $grid->column('level', __('等级'));
        $grid->column('total', __('充值总计'));
        $grid->column('ratio', __('返利(%)'));
        $grid->column('实返R币')->display(function(){
            return $this->total * ($this->ratio / 100);
        });
        $grid->column('description', __('描述'));
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(RechargeRebate::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('level', __('等级'));
        $show->field('total', __('充值总计'));
        $show->field('ratio', __('返利(%)'));
        $show->field('description', __('描述'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new RechargeRebate());

        $form->number('level', __('等级'));
        $form->number('total', __('充值总计'));
        $form->number('ratio', __('返利(%)'));
        $form->textarea('description', __('描述'));

        return $form;
    }
}
