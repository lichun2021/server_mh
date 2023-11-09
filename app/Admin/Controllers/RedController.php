<?php

namespace App\Admin\Controllers;

use App\Red;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RedController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '红包活动';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Red());
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
        });
        //过滤器
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->column(1/2, function ($filter) {

            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('title','标题');
            });
        });
        $grid->column('id', 'Id');
        $grid->column('title', '标题');
        $grid->column('briefe', '简述');
        $grid->column('min_recharge', '充值金额');
        $grid->column('pay_start_time', '充值计算起始时间');
        $grid->column('num', '红包个数');
        $grid->column('remainder', '剩余');
        $grid->column('percentage', '红包区间('.getConfig("bean_name").')')->display(function (){
            $value = $this->percentage;
            if (is_array($value) && count($value) === 2){
                return $value[0].'/'.$value[1];
            }
            throw new \Exception('红包区间设置有误！');
        });
        $grid->column('start_time', '开始时间');
        $grid->column('end_time', '结束时间');
        $grid->column('status', '状态')->using(Red::$fields['status'])->dot([
            0 => 'info',
            1 => 'success',
            2 => 'warning',
            3 => 'danger'
        ],'warning');
        $grid->column('created_at', __('创建时间'));
        //$grid->column('updated_at', __('Updated at'));

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
        $show = new Show(Red::findOrFail($id));

        $show->field('id', 'Id');
        $show->field('title', '标题');
        $show->field('briefe','简述');
        $show->field('min_recharge', '充值'.getConfig('bean_name'));
        $show->field('pay_start_time', '充值计算起始时间');
        $show->field('num', '红包个数');
        $show->field('percentage', '面值(总充值%)');
        $show->field('start_time', '开始时间');
        $show->field('end_time', '结束时间');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '最后更新时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Red());

        $form->text('title', '标题')->rules(['required', 'max:32'],['required' => '标题不能为空', 'max' => '标题不能大于32个字符']);
        $form->textarea('briefe', '简述')->rules(['max:128'], ['max' => '简述不能大于128个字符']);
        $form->currency('min_recharge', getConfig('bean_name'))->rules(['required', 'numeric', 'min:1'],[
            'required' => '充值金额不能为空', 'numeric' => '充值金额必须是数字', 'min' => '充值金额最小值为1'
        ])->help('从充值起始时间开始计算，开启红包最小充值'.getConfig('bean_name'))->symbol('M');
        //$form->datetime('pay_start_time', '充值计算起始时间')->default(date('Y-m-d H:i:s'))->required()->help('充值计算起始时间');
        $form->number('num', '红包个数')->rules(['required', 'integer', 'min:1', 'max:10000'],['required' => '红包个数不能为空', 'integer' => '红包个数必须是数字', 'min' => '红包个数最小值为1', 'max' => '红包个数最大值支持到10000'])->help('红包总份数');
        $form->text('percentage', '面值')->rules([
            'required'
        ],[
            'required' => '总充值百分比不能为空'
        ])->help('红包区间值 随机面值如：0.5/10 固定面值：0.5/0.5');
        $form->datetime('start_time', '开始时间')->default(date('Y-m-d').' 00:00:00')->required();
        $form->datetime('end_time', '结束时间')->default(date('Y-m-d', strtotime('+1 day')). '00:00:00')->required();
        $form->saving(function (Form $form) {
            $percentage =  explode('/',trim($form->percentage));
            if (count($percentage) !== 2){
                throw new \Exception('红包面值错误！');
            } elseif (count($percentage) == 2){
                if (!is_numeric($percentage[0]) || !is_numeric($percentage[1])){
                    throw new \Exception('红包开始或结束值输入错误！');
                }
                $form->percentage = $percentage;
            }
        });

        return $form;
    }
}
