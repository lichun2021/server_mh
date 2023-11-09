<?php

namespace App\Admin\Controllers;

use App\StarsList;
use App\Admin\Actions\Stars\AddContain;
use App\Admin\Actions\Stars\ViewContain;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StarsListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '红星轮盘';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StarsList());
        //筛选
        //筛选
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('name', '名称');
        });

        $grid->model()->orderBy('sort');
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->add(new ViewContain());
            $actions->add(new AddContain());
        });

        $grid->column('id', 'Id');
        $grid->column('name', __('Name'));
        $grid->column('cover', '图片')->lightbox(['width' => 50]);
        $grid->column('bean', getConfig('bean_name'))->editable();
        $grid->column('sort', __('Sort'))->editable();
        $states = [
            'on'  => ['value' => 1, 'text' => '正常', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'danger'],
        ];
        $grid->column('status', __('Status'))->switch($states);
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $script = <<<EOT
$.fn.modal.Constructor.prototype.enforceFocus = function () {};
$("div[id^='grid-modal-']").removeAttr('tabindex');
$("div[id='modal']").removeAttr('tabindex');
EOT;
        \Encore\Admin\Facades\Admin::script($script);

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
        $show = new Show(StarsList::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('bean', __('Bean'));
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
        $form = new Form(new StarsList());

        $form->text('name', __('Name'))->rules('required|max:50',[
            'required' => '名称不能为空',
            'max' => '名称不得超过50个字符',
        ]);
        $form->image('cover', '图片')->move('images/stars')->rules('required',[
            'required' => '图片不能为空'
        ])->uniqueName();
        $form->decimal('bean', getConfig('bean_name'))->default(0.1)->rules('required|numeric|min:0.1',[
            'required' => '请输入'.getConfig('bean_name'),
            'number' => getConfig('bean_name').'输入有误',
            'min' => getConfig('bean_name').'不得小于0.1',
        ]);
        $form->number('sort', __('Sort'))->default(0)->rules('required|integer|min:0|max:65535',[
            'required' => '排序不得为空',
            'integer' => '排序必须为整数',
            'min' => '排序不得小于0',
            'max' => '排序不得大于65535',
        ]);
        $states = [
            'on'  => ['value' => 1, 'text' => '正常', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'danger'],
        ];
        $form->switch('status', __('Status'))->states($states)->default(0);
        return $form;
    }
}
