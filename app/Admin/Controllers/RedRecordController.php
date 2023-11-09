<?php

namespace App\Admin\Controllers;

use App\RedRecord;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RedRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '红包记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RedRecord());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用所有操作
        $grid->disableActions();
        //过滤器
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->like('user.name', '用户名');
                $filter->equal('red_key', '口令');
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->like('red.title', '红包标题');
                $filter->equal('type', '来源')->select([1 => '红包活动', 2 => '口令红包']);
            });
        });
        $grid->model()->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('red.title', '红包');
        $grid->column('red_key', '口令');
        $grid->column('user.name', '用户');
        $grid->column('bean', getConfig('bean_name'));
        $grid->column('type', '来源')->using(RedRecord::$fields['type']);
        $grid->column('created_at', '时间');
        //$grid->column('updated_at', __('Updated at'));

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
        $show = new Show(RedRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('red_id', __('Red id'));
        $show->field('user_id', __('User id'));
        $show->field('bean', __('Bean'));
        $show->field('type', __('Type'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new RedRecord());

        $form->number('red_id', __('Red id'));
        $form->number('user_id', __('User id'));
        $form->decimal('bean', __('Bean'));
        $form->switch('type', __('Type'));

        return $form;
    }
}
