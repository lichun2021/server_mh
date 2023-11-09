<?php

namespace App\Admin\Controllers;

use App\YouPinApi;
use Encore\Admin\Grid;
use App\Admin\Actions\YouPin\Buy;
use Encore\Admin\Controllers\AdminController;
class YouPinController extends AdminController
{
    protected $title = '有品商品列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new YouPinApi());
        $grid->disableCreateButton();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        $grid->disableFilter();
        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            $actions->add(new Buy());
        });
        $grid->column('commodityName', '装备名称');
        $grid->column('imageUrl', '图片')->image('', 75);
        $grid->column('bean', getConfig('bean_name'));
        $grid->column('commodityPrice', 'RMB币');
        return $grid;
    }
}
