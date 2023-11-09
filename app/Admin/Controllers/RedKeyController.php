<?php

namespace App\Admin\Controllers;

use App\RedKey;
use App\Admin\Actions\Red\GenerateKey;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class RedKeyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '红包口令';

    /**
     * @var array[]
     */
    protected static $states = [
        'on' => ['value' => 1, 'text' => '打开', 'color' => 'success'],
        'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RedKey());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用行选择器
        //$grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用所有操作
        $grid->disableActions();

        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->like('code', '口令');
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('status', '状态')->select([0 => '关闭', 1 => '打开']);
            });
        });
        $grid->export(function ($export) {
            $export->filename('红包CDK_'.date('Y-m-d'));
            $export->column('status', function ($value, $original) {
                if ($original === 0){
                    return '关闭';
                }
                return '打开';
            });
            $export->column('quantity_used', function ($value, $original) {
                return $original;
            });
        });
        //生产卡密
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new GenerateKey());
        });

        $grid->model()->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('code', '红包口令');
        $grid->column('threshold', '充值门槛')->display(function ($threshold) {
            if ($threshold <= 0) {
                return null;
            }
            return $threshold;
        });
        $grid->column('denomination', '面值')->display(function ($denomination) {
            if (empty($denomination)) {
                return null;
            }
            if ($denomination[0] === $denomination[1]) {
                return $denomination[0];
            }
            return $denomination[0] . ' - ' . $denomination[1];
        });
        $grid->column('quantity', '可用次数');
        $grid->column('quantity_used', '已使用')->display(function () {
            $used = bcmul($this->quantity_used / $this->quantity, 100, 2);
            return <<<EOT
<div class="row" style="min-width: 100px;">
    <span class="col-sm-3" style="color:#777;">{$used}%</span>
    <div class="progress progress-sm col-sm-9" style="padding-left: 0;padding-right:0; width: 100px;">
        <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="{$used}" aria-valuemin="0" aria-valuemax="{$this->quantity}" style="width: {$used}%">
        </div>
    </div>
</div>
EOT;
        });
        $grid->column('status', '状态')->switch(self::$states);
        $grid->column('created_at', '创建时间');

        return $grid;
    }

    /**
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new RedKey());
        $form->switch('status')->states(self::$states);
        return $form;
    }
}
