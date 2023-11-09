<?php

namespace App\Admin\Controllers;

use App\UserRewardLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RewardLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '奖励MU币记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserRewardLog());
        $grid->model()->orderBy('id','desc');
        //禁用创建按钮
        $grid->disableCreateButton();
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/2, function ($filter) {
                $filter->like('user.name', '用户');
            });
            $filter->column(1/2, function ($filter) {

                $filter->equal('type', '奖励类型')->select(UserRewardLog::$fields['type']);
            });
        });
        $grid->export(function ($export) {
            $export->except(['描述']);
        });
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用所有操作
        $grid->disableActions();
        $grid->column('id', __('Id'));
        $grid->column('user.name', __('用户'));
        $grid->column('user_id', __('用户ID'));
        $grid->column('奖励类型')->display(function(){
            return UserRewardLog::$fields['type'][$this->type];
        });
        $grid->column('next_user_id', __('下级用户'));
        $grid->column('bean', getConfig('bean_name'));
        $grid->column('created_at', __('奖励时间'));

        return $grid;
    }
}
