<?php

namespace App\Admin\Controllers;

use App\AwardLevel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AwardLevelController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '奖品等级';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AwardLevel());
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

        $grid->column('id', __('Id'));
        $grid->column('name', __('等级'));
        $grid->column('bg_image', '背景')->image('', 50);
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
        $show = new Show(AwardLevel::findOrFail($id));
        $show->field('id', __('Id'));
        $show->field('name', __('等级名称'));
        $show->field('bg_image', __('背景图片'));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AwardLevel());
        $form->text('name', '名称');
        $form->image('bg_image', '背景')->uniqueName();
        return $form;
    }
}
