<?php

namespace App\Admin\Controllers;

use App\PromotionLevel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PromotionLevelController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '推广返佣';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PromotionLevel());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用分页
        $grid->disablePagination();
        //禁用查询过滤器
        $grid->disableFilter();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();

        $grid->column('id', __('Id'));
        $grid->column('name', __('称谓'));
        $grid->column('level', __('等级'));
        $grid->column('invite_total', __('下级累计充值'))->help('下级用户累计充值到达条件自动升级。');
        $grid->column('rebate', __('返佣比例(%)'));
        $grid->column('reg_rebate', __('注册赠送'));
        $grid->column('description', __('描述'));

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
        $show = new Show(PromotionLevel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('称谓'));
        $show->field('level', __('等级'));
        $show->field('invite_total', __('下级累计充值'));
        $show->field('rebate', __('返佣比例(%)'));
        $show->field('reg_rebate', __('注册赠送'));
        $show->field('description', __('描述'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PromotionLevel());

        $form->text('name', __('称谓'))->rules(['required']);
        $form->number('level', __('等级'))->rules(['required'])->disable();
        $form->currency('invite_total', __('下级累计充值'))->rules(['required'])->help('下级用户累计充值到达条件自动升级。')->symbol('M');
        $form->currency('rebate', __('返佣比例(%)'))->symbol('%');
        $form->currency('reg_rebate', __('注册赠送'))->symbol('M')->help('邀请用户注册充后赠送R币。');
        $form->textarea('description', __('描述'));

        return $form;
    }
}
