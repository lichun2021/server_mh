<?php

namespace App\Admin\Controllers;

use App\WelfareRecord;
use App\Welfare;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class WelfareRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '福利开箱记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WelfareRecord());
        //禁用创建
        $grid->disableCreateButton();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->disableIdFilter();
            $filter->like('user.name', '用户');
        });
        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->model()->orderByDesc('id');
        $grid->column('id', __('Id'));
        $grid->column('user.name', __('用户'));
        $grid->column('type', __('类型'))->using(Welfare::$fields['type']);
        $grid->column('条件')->display(function (){
            if ($this->type == 1){
                return '每日福利箱';
            } elseif ($this->type == 2){
                return '每充值 $'. $this->other;
            } elseif ($this->type == 3){
                return '用户充值 $'. $this->other;
            } else {
                return '无';
            }
        });
        $grid->column('box.name', __('宝箱'));
        $grid->column('created_at', __('开箱日期'));

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
        $show = new Show(WelfareRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('type', __('Type'));
        $show->field('box_id', __('Box id'));
        $show->field('other', __('Other'));
        $show->field('time', __('Time'));
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
        $form = new Form(new WelfareRecord());

        $form->number('user_id', __('User id'));
        $form->switch('type', __('Type'));
        $form->number('box_id', __('Box id'));
        $form->text('other', __('Other'));
        $form->number('time', __('Time'));

        return $form;
    }
}
