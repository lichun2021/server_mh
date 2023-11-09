<?php

namespace App\Admin\Controllers;

use App\Welfare;
use App\WelfareCdk;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Controllers\AdminController;
use App\Admin\Actions\welfare\CdkGenerate;

class WelfareCdkController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'CDK宝箱兑换码';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WelfareCdk());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用所有操作
        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableEdit();
            $actions->disableView();
        });
        //生产卡密
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new CdkGenerate());
        });
        //过滤器
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->column(1/3, function ($filter) {
                $filter->equal('key', '兑换码');
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('welfare_id', 'CDK宝箱')->select(Welfare::where(['type' => 4])->pluck('name','id'));
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('status','状态')->select(WelfareCdk::$fields['status']);
            });
        });
        
        $grid->model()->orderBy('status')->orderBy('id', 'DESC');
        $grid->column('id', 'Id');
        $grid->column('welfare.name', 'CDK宝箱')->display(function (){
            if ($this->welfare === null){
                return null;
            }
            return $this->welfare->name;
        });
        $grid->column('key', 'CDK兑换码');
        $grid->column('status', '状态')->using(WelfareCdk::$fields['status'])->dot([
            0 => 'danger',
            1 => 'success'
        ], 'warning');
        $grid->column('user.name', '使用用户');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '使用时间');

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
        $show = new Show(WelfareCdk::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('key', __('CDK兑换码'));
        $show->field('status', __('状态'));
        $show->field('user_id', __('用户'));
        $show->field('created_at', __('创建时间'));
        $show->field('updated_at', __('使用时间'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new WelfareCdk());
        //  var_dump(strtoupper(Str::uuid()));
        $form->text('key', __('Key'));
        $form->switch('status', __('CDK兑换码'));
        $form->number('user_id', __('用户'));

        return $form;
    }
}
