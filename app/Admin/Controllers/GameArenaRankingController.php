<?php

namespace App\Admin\Controllers;

use App\GameRanking;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;

class GameArenaRankingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '对战排行榜';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GameRanking());
        $grid->model()->orderByDesc('date')->orderByDesc('expend')->orderBy('id');
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用操作
        $grid->disableActions();
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('date', '日期')->date();
        });

        $grid->column('id', 'Id');
        $grid->column('user.avatar', '用户头像')->image('', 50);
        $grid->column('user.name', '用户名');
        $grid->column('expend', '花费');
        $grid->column('date', '日期');
        return $grid;
    }
}
