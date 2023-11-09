<?php

namespace App\Admin\Controllers;

use App\Box;
use App\BoxCate;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BoxCateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '宝箱分类';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BoxCate());
        //禁用查询过滤器
        $grid->disableFilter();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
        });
        $grid->model()->orderBy('sort');
        $grid->column('id', __('Id'));
        $grid->column('name', __('名称'))->editable();
        $grid->column('src', '图标')->image('','',30);
        $grid->column('sort', __('Sort'))->editable();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(BoxCate::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('src', '图标');
        $show->field('sort', __('Sort'));
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
        $form = new Form(new BoxCate());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
            $footer->disableViewCheck();

        });
        $form->text('name', __('Name'))->rules(['required','min:0','max:50'],[
            'required' => '请输入宝箱分类名称',
            'min' => '宝箱分类名称最小2个字符',
            'max' => '宝箱分类名称最大50个字符',
        ]);
        $form->image('src', '图标')->uniqueName();
        $form->number('sort', __('Sort'))->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:65535',
        ],[
            'required' => '请输入排序',
            'integer' => '排序必须为整数',
            'min' => '排序最小值支持0 最大值支持 65535',
            'max' => '排序最小值支持0 最大值支持 65535',
        ]);
        //保存后回调
        $form->saved(function (Form $form) {
            //清理宝箱列表缓存
            \Cache::delete(Box::$fields['cacheKey'][1]);
            //清除分类缓存
            \Cache::delete(BoxCate::$fields['cacheKey']);
        });
        return $form;
    }
}
