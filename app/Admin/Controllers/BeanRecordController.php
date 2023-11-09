<?php

namespace App\Admin\Controllers;

use App\Bean;
use App\User;
use App\BeanRecord;
use App\BaiduChannel;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Controllers\AdminController;

class BeanRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '充值记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BeanRecord());
        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        // 在这里添加字段过滤器
        $grid->filter(function ($filter) {
            $filter->column(1 / 3, function ($filter) {
                $filter->disableIdFilter();
                $filter->equal('user_id', '用户ID');
                $filter->like('code', '订单号');
                $filter->equal('user.baidu_channel_id', '渠道')->select(BaiduChannel::all()->pluck('name','id'));
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->like('user.name', '用户名');
                $filter->between('created_at', '充值日期')->date();
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->equal('inviter_id', '主播ID');
                $filter->equal('status', '状态')->select([0 => '未付款', 1 => '已付款']);
            });
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->model()->orderBy('id', 'DESC');
        //头部工具
        $grid->header(function ($query) {
            // 查询出已支付状态的订单总金额
            $data = $query->where('status', 1)->where('is_pay_api', 1)->sum('bean');
            return "<div style='padding: 10px;'>查询结果".getConfig('bean_name')."统计 ： $data</div>";
        });

        $grid->column('id', __('Id'));
        $grid->column('user.name', __('用户名'));
        $grid->column('user.id', '用户Id');
        $grid->column('bean', getConfig('bean_name'));
        $grid->column('code', __('订单号'));
        $grid->column('trade_no', __('支付平台单号'));
        $grid->column('status', __('状态'))->using(BeanRecord::$fields['status'])->dot([
            0 => 'danger',
            1 => 'success'
        ], 'warning')->sortable();
        $grid->column('created_at', __('下单时间'));

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
        $show = new Show(BeanRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('bean', __('R币'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('deleted_at', __('Deleted at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BeanRecord());

        $beans = Bean::get()->toArray();
        $price = array_column($beans, null, 'bean');
        $beanArray = [];
        foreach ($beans as $bean) {
            $beanArray[$bean['bean']] = $bean['bean'];
        }
        $user = User::query()->pluck('name', 'id')->toArray();
        $form->select('user_id', __('用户ID'))->options($user)->rules(['required', 'exists:users,id'], ['required' => '用户ID不能为空！', 'exists' => '用户ID不存在！']);
        $form->select('bean', __('R币'))->options($beanArray)->required();
        $form->hidden('price')->disableHorizontal();
        $form->hidden('code');
        $form->radioButton('status', __('状态'))->options([0 => '未付款', 1 => '已付款'])->default(1);
        $form->saving(function (Form $form) use ($price) {
            $form->price = $price[$form->bean]['price'];
            $form->code = date('YmdHis') . random_int(1000, 9999);
        });
        return $form;
    }
}
