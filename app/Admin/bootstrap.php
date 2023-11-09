<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */
use App\Admin\Actions;

Encore\Admin\Form::forget(['map', 'editor']);
app('view')->prependNamespace('admin', resource_path('views/admin'));
Admin::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) {
    $navbar->right(new Actions\ClearCache());
});
//初始化表单
\Encore\Admin\Form::init(function (\Encore\Admin\Form $form){
    $form->disableEditingCheck();
    $form->disableViewCheck();
    $form->disableCreatingCheck();
    $form->tools(function (\Encore\Admin\Form\Tools $tools) {
        // 去掉`删除`按钮
        $tools->disableDelete();
        // 去掉`查看`按钮
        //$tools->disableView();
    });
});
//初始化查看
\Encore\Admin\Show::init(function (\Encore\Admin\Show $show){
    $show->panel()
        ->tools(function ($tools) {
            $tools->disableDelete();
        });
});

