<?php

namespace App\Admin\Controllers;

use App\BeanChangeRecord;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BeanChangeRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '收支明细';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BeanChangeRecord());

        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用操作
        $grid->disableActions();
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
                $filter->equal('user_id', '用户Id');
                $filter->equal('type', '类型')->select(BeanChangeRecord::$fields['type']);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('user.mobile', '手机号')->mobile();
                $filter->equal('change_type', '场景')->select(BeanChangeRecord::$fields['change_type']);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('user.name', '用户昵称');

            });
        });
        $grid->export(function ($export) {
            $export->column('user.name', function ($value, $original) {
                return $original;
                
            });
            $export->column('bean', function ($value, $original){
                if ($original > 0){
                    return '+'.$original;
                }
                return $original;
            });
            $export->column('type', function ($value, $original){
                return BeanChangeRecord::$fields['type'][$original];
            });
        });

        $grid->model()->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('user.name', '用户名')->display(function (){
            return '<a href="'.admin_url('bean-change-records?user_id='.$this->user_id).'">'.$this->user->name.'</a>';
        });
        $grid->column('user.id', '用户Id');
        $grid->column('change_type', '场景')->using(BeanChangeRecord::$fields['change_type']);
        $grid->column('bean', '金额')->display(function ($bean){
            if ($bean > 0){
                return '<div style="color: #008d4c">+'.$bean.'</div>';
            }
            return '<div style="color: #d9534f">'.$bean.'</div>';
        });
        $grid->column('type', '类型')->using(BeanChangeRecord::$fields['type'])->label([
            0 => 'danger',
            1 => 'success',
        ]);
        $grid->column('final_bean', '余额');
        $grid->column('created_at', '时间');

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
        $show = new Show(BeanChangeRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('final_bean', __('Final bean'));
        $show->field('bean', __('Bean'));
        $show->field('type', __('Type'));
        $show->field('change_type', __('Change type'));
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
        $form = new Form(new BeanChangeRecord());

        $form->number('user_id', __('User id'));
        $form->decimal('final_bean', __('Final bean'))->default(0.00);
        $form->decimal('bean', __('Bean'))->default(0.00);
        $form->switch('type', __('Type'));
        $form->switch('change_type', __('Change type'));

        return $form;
    }
}
