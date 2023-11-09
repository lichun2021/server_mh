<?php

namespace App\Admin\Controllers;

use App\Card;
use App\Admin\Actions\Card\Generate;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CardController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '卡密';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Card());
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
        // 在这里添加字段过滤器
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->like('number', '卡号');
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('status', '状态')->select([0 => '未使用', 1 => '已使用']);
            });
        });
        //生产卡密
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Generate());
        });
        $grid->model()->orderBy('status')->orderBy('id', 'DESC');
        $grid->column('id', __('Id'));
        $grid->column('number', __('卡号'));
        $grid->column('bean', getConfig('bean_name'));
        $grid->column('status', __('状态'))->using([0 => '未使用', 1 => '已使用'])->dot([
            0 => 'info',
            1 => 'success',
        ], 'warning');
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at','使用时间');

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
        $show = new Show(Card::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('number', __('Number'));
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
        $form = new Form(new Card());

        $form->text('number', __('Number'));
        $form->switch('status', __('Status'));

        return $form;
    }
}
