<?php

namespace App\Admin\Controllers;

use App\DeliveryRecord;
use App\Admin\Actions\Zbt\StateSyn as ZbtSyn;
use App\Admin\Actions\Bus\StateSyn as BusSyn;
use App\Admin\Actions\YouPin\StateSyn as YouPinSyn;
use App\Admin\Actions\V5Item\StateSyn as V5Syn;
use App\Admin\Actions\Zbt\CancelOrder as ZbtCancelOrder;
use App\Admin\Actions\Bus\CancelOrder as BusCancelOrder;
use App\Admin\Actions\YouPin\CancelOrder as YouPinCancelOrder;
use App\Admin\Actions\V5Item\CancelOrder as V5CancelOrder;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DeliveryRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '发货记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DeliveryRecord());
        //禁用创建按钮
        $grid->disableCreateButton();
        //筛选
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            //$filter->disableIdFilter();
            $filter->column(1 / 3, function ($filter) {
                $filter->like('record.name', '饰品名称');
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->equal('user_id', '用户ID');
                $filter->between('created_at', '日期')->datetime();
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->like('user.name', '用户名');
                $filter->like('platform', '平台')->select([3 => '悠悠', 4 => 'V5']);
            });
        });
        //禁用列选择器
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            if (!in_array($actions->row->zbt_status, [10, 11])) {
                if ($actions->row->platform === 1) {
                    $actions->add(new ZbtSyn());
                } elseif ($actions->row->platform === 3) {
                    $actions->add(new YouPinSyn());
                } elseif ($actions->row->platform === 4) {
                    $actions->add(new V5Syn());
                } else {
                    $actions->add(new BusSyn());
                }
            }
            if ($actions->row->zbt_status === 1) {
                if ($actions->row->platform === 1) {
                    $actions->add(new ZbtCancelOrder());
                } elseif ($actions->row->platform === 3) {
                    $actions->add(new YouPinCancelOrder());
                } elseif ($actions->row->platform === 4) {
                    $actions->add(new V5CancelOrder());
                } else {
                    $actions->add(new BusCancelOrder());
                }
            }
        });
        $grid->export(function ($export) {
            $export->column('record.cover', function ($value, $original) {
                return $original;

            });
            $export->column('delivery', function ($value, $original) {
                if ($original === 1) {
                    return '人工';
                }
                return '自动';
            });
            $export->column('record_code', function ($value, $original) {
                return 'A' . $original;
            });
            $export->column('zbt_status', function ($value, $original) {
                if ($original === 1) {
                    return '等待卖家发送报';
                } elseif ($original === 3) {
                    return '等待接受';
                } elseif ($original === 10) {
                    return '交易完成';
                } elseif ($original === 11) {
                    return '已取消退回申请列表';
                }
                return null;
            });
        });

        $grid->model()->orderByDesc('id');

        $grid->column('id', __('Id'));
        $grid->column('user.name', '用户');
        $grid->column('user.id', '用户Id');
        $grid->column('record.name', '饰品名称');
        $grid->column('record.cover', '饰品图片')->image(null, 75);
        $grid->column('record_code', '仓库单号');
        $grid->column('trade_no', '订单号');
        $grid->column('price', '花费RMB');
        $grid->column('platform', '平台')->using([1 => 'ZBT', 3 => '悠悠', 4 => 'V5']);
        $grid->column('delivery', '发货类型')->using([0 => '未知', 1 => '人工', 2 => '自动'])->dot([
            1 => 'warning',
            2 => 'success',
        ], 'warning');
        $grid->column('order_id', '平台订单号');
        $grid->column('zbt_status', '发货状态')->using([1 => '等待卖家发送报价', 3 => '等待接受', 10 => '交易完成', 11 => '已取消退回'])->dot([
            1 => 'warning',
            3 => 'info',
            10 => 'success',
            11 => 'danger',
        ], 'warning');
        $grid->column('updated_at', '最后操作时间');

        $grid->header(function ($query) {
            $total = DeliveryRecord::query()->where(['zbt_status' => 10])->sum('price');
            $today = DeliveryRecord::query()->where(['zbt_status' => 10])->where('created_at', 'like', date('Y-m-d') . '%')->sum('price');
            $filter = $query->where(['zbt_status' => 10])->sum('price');

            return '今日发货：<b>' . $today . '</b> RMB<br>总发货：<b>' . $total . '</b> RMB<br>显示结果统计：<b>' . $filter . '</b> RMB';
        });

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
        $show = new Show(DeliveryRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('record_id', __('Record id'));
        $show->field('record_code', __('Record code'));
        $show->field('trade_no', __('Trade no'));
        $show->field('price', __('Price'));
        $show->field('delivery', __('Delivery'));
        $show->field('order_id', __('Order id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }
}
