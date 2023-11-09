<?php

namespace App\Admin\Controllers;

use App\UserTagList;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class UserTagListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '用户标签';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserTagList());
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用行选择checkbox
        $grid->disableRowSelector();
        //
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->like('name', '名称');
        });

        $grid->column('id', 'Id');
        $grid->column('name', '名称');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserTagList());
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        $form->text('name', '名称');

        return $form;
    }
}
