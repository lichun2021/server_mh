<?php

namespace App\Admin\Controllers;

use App\FirstRechargeOffer;
use App\Bean;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FirstRechargeOfferController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '首冲奖励';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FirstRechargeOffer());
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
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->column('id', __('Id'));
        $grid->column('beans_id', '对应充值'.getConfig('bean_name').'列表ID');
        $grid->column('对应充值'.getConfig('bean_name'))->display(function () {
            $bean = Bean::find($this->beans_id);
            return $bean->bean ?? 0;
        });
        $grid->column('关系生效状态')->display(function () {
            $bean = Bean::find($this->beans_id);
            return !empty($bean->id) ? '<span class="label label-success" title="对应R币充值列表关系正常">正常</span>' : '<span class="label label-danger" title="对应R币充值列表已被删除，首冲关系失效！">无效</span>';
        });
        $grid->column('ratio', __('奖励比率（%）'));
        $grid->column('实际到账')->display(function () {
            $bean = Bean::find($this->beans_id);
            return !empty($bean->bean) ? $bean->bean + ($bean->bean * ($this->ratio / 100)) : 0;
        });
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
        $show = new Show(FirstRechargeOffer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('beans_id', '对应充值'.getConfig('bean_name').'列表');
        $show->field('ratio', __('奖励比率（%）'));
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

        $form = new Form(new FirstRechargeOffer());

        $form->select('beans_id', '对应充值'.getConfig('bean_name').'列表')->options(function () {
            $beans = Bean::get()->toArray();
            $beanArray = [];
            foreach ($beans as $bean) {
                $beanArray[$bean['id']] = $bean['bean'];
            }
            return $beanArray;
        });
        $form->number('ratio', __('奖励比率（%）'));
        $form->textarea('description', __('描述'));

        return $form;
    }
}
