<?php

namespace App\Admin\Controllers;

use App\AwardType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AwardTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '幸运开装备类型';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AwardType());
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
        $grid->column('id', __('Id'));
        $grid->column('name', __('类型'));
        $grid->column('cover', __('封面'))->image('',75);
        $grid->column('sort', __('排序'))->editable();

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
        $show = new Show(AwardType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('类型'));
        $show->field('cover', __('封面'));
        $show->field('sort', __('排序'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AwardType());

        $form->text('name', __('类型'));
        $form->image('cover', __('封面'))->uniqueName();
        $form->number('sort', __('排序'));

        return $form;
    }
}
