<?php

namespace App\Admin\Controllers;

use App\SensitiveWord;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class SensitiveWordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '敏感词';

    protected $description = [
        'index'  => '列表'
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SensitiveWord());
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->disableIdFilter();
            $filter->like('word', '敏感词汇');
        });
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->model()->orderBy('id', 'DESC');
        $grid->column('word', __('敏感词汇'));

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SensitiveWord());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`删除`按钮
            $tools->disableDelete();
        });
        $form->footer(function ($footer) {
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
            $footer->disableViewCheck();

        });
        $form->text('word', __('敏感词汇'));
        $form->saved(function (Form $form) {
            //保存后清除缓存
            Cache::delete(SensitiveWord::$cacheKey);
        });
        return $form;
    }
}
