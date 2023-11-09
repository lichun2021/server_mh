<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/30
 * Time: 23:02
 */

namespace App\Admin\Controllers;

use App\Admin\Actions\Zbt\Zbt;
use App\ZbtApi;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\AdminController;
class ZbtController extends AdminController
{
    protected $title = 'ZBT在售列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ZbtApi());
        $grid->disableCreateButton();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
                $filter->equal('delivery', '发货方式')->select([
                    1 => '人工',
                    2 => '自动',
                ]);
            });
        });
        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            $actions->add(new Zbt());
        });
        $grid->column('itemName', __('装备名称'));
        $grid->column('imageUrl', __('图片'))->image('', 75);
        $grid->column('cnyPrice', 'RMB币');
        $grid->column('price', __('T币'));
        $grid->column('delivery', __('发货方式'))->using([1 => '人工发货' ,2 => '自动发货'])->dot([
            1 => 'warning',
            2 => 'success',
        ], 'warning');
        return $grid;
    }
}
