<?php

namespace App\Admin\Controllers;

use App\Skins;
use App\SyntheRecord;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SyntheRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '汰换合同';

    protected $description = [
        'index' => '记录'
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SyntheRecord());
        //禁用创建按钮
        $grid->disableCreateButton();
        //查询过滤器
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
                $filter->like('user.name', '用户名')->placeholder('请输入用户名');;
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('award_name', '合成饰品')->placeholder('请输入饰品名');
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('status', '合成饰品')->select([0 => '平台赚', 1 => '平台赔', 2 => '平局']);
            });
        });
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->disableActions();
        $grid->model()->orderByDesc('id');

        $grid->column('id', 'Id');
        $grid->column('user.name', '用户');
        $grid->column('use_bean', '消耗饰品总价');
        $grid->column('award.cover', '获得饰品封面')->lightbox(['width' => 75]);
        $grid->column('award_name', '获得饰品名称')->display(function () {
            return $this->award_name . ' (' . Skins::$fields['dura'][$this->award_dura] . ')';
        });
        $grid->column('bean', __('获得饰品价值'));
        $grid->column('status', __('汰换状态'))->using([0 => '平台赚', 1 => '平台赔', 3 => '平局'])->dot([
            0 => 'success',
            1 => 'danger'
        ], 'warning');
        $grid->column('created_at', __('汰换时间'));

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
        $show = new Show(SyntheRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('use_bean', __('Use bean'));
        $show->field('award_id', __('Award id'));
        $show->field('award_name', __('Award name'));
        $show->field('award_dura', __('Award dura'));
        $show->field('award_lv', __('Award lv'));
        $show->field('bean', __('Bean'));
        $show->field('status', __('Status'));
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
        $form = new Form(new SyntheRecord());

        $form->number('user_id', __('User id'));
        $form->decimal('use_bean', __('Use bean'));
        $form->number('award_id', __('Award id'));
        $form->text('award_name', __('Award name'));
        $form->switch('award_dura', __('Award dura'));
        $form->switch('award_lv', __('Award lv'));
        $form->decimal('bean', __('Bean'));
        $form->switch('status', __('Status'));

        return $form;
    }
}
