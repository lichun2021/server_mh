<?php

namespace App\Admin\Controllers;

use App\LuckyBoxRecord;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LuckyBoxRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '幸运开箱';

    protected $description = [
        'index'  => '记录'
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LuckyBoxRecord());
        //禁用创建按钮
        $grid->disableCreateButton();
        //查询过滤器
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('user.name', '用户');
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('award_name', '目标饰品');
            });
        });
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->disableActions();
        $grid->model()->orderByDesc('id');

        $grid->column('id', __('Id'));
        $grid->column('user.name', __('用户'));
        $grid->column('use_bean', '消耗'.getConfig('bean_name'));
        $grid->column('bean', __('目标价值'));
        $grid->column('get_bean', __('获得价值'));
        $grid->column('award.cover', __('目标饰品封面'))->lightbox(['width' => 75]);
        $grid->column('award_name', __('目标饰品'))->display(function (){
            return $this->award_name.' ('. LuckyBoxRecord::$fields['dura'][$this->award_dura] .')';
        });
        $grid->column('get_award.cover', __('获得饰品封面'))->lightbox(['width' => 75]);
        $grid->column('get_award_name', __('获得饰品'))->display(function (){
            return $this->get_award_name.' ('. LuckyBoxRecord::$fields['dura'][$this->get_award_dura] .')';
        });
        $grid->column('created_at', __('开启时间'));

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
        $show = new Show(LuckyBoxRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('use_bean', __('Use bean'));
        $show->field('award_id', __('Award id'));
        $show->field('award_name', __('Award name'));
        $show->field('award_dura', __('Award dura'));
        $show->field('award_lv', __('Award lv'));
        $show->field('bean', __('Bean'));
        $show->field('get_award_id', __('Get award id'));
        $show->field('get_award_name', __('Get award name'));
        $show->field('get_award_dura', __('Get award dura'));
        $show->field('get_award_lv', __('Get award lv'));
        $show->field('get_bean', __('Get bean'));
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
        $form = new Form(new LuckyBoxRecord());

        $form->number('user_id', __('User id'));
        $form->decimal('use_bean', __('Use bean'));
        $form->number('award_id', __('Award id'));
        $form->text('award_name', __('Award name'));
        $form->switch('award_dura', __('Award dura'));
        $form->switch('award_lv', __('Award lv'));
        $form->decimal('bean', __('Bean'));
        $form->number('get_award_id', __('Get award id'));
        $form->text('get_award_name', __('Get award name'));
        $form->switch('get_award_dura', __('Get award dura'));
        $form->switch('get_award_lv', __('Get award lv'));
        $form->decimal('get_bean', __('Get bean'));

        return $form;
    }
}
