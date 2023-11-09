<?php

namespace App\Admin\Controllers;

use App\LoginIpLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Database\Eloquent\Collection;

class LoginIpLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '登录Ip记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LoginIpLog());
        //禁用新增按钮
        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用所有操作
        $grid->disableActions();
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->disableIdFilter();
            $filter->column(1/3, function ($filter) {
                $filter->like('ip', 'Ip');
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('user.name', '用户名');
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('user.mobile', '手机号');
            });
        });
        if (request()->get('user_id')){
            $grid->model()->where('user_id',request()->get('user_id'))->orderBy('id', 'DESC');
        }else{
            $grid->model()->orderBy('id', 'DESC');
        }

        $grid->model()->collection(function (Collection $collection) {
            foreach($collection as $item) {
                $query = \Ip::find($item->ip);
                $item->address = implode(' - ',$query);
            }
            return $collection;
        });
        $grid->column('id', __('Id'));
        $grid->column('user.name', '用户名');
        $grid->column('user.mobile', '手机号');
        $grid->column('ip', '登录Ip');
        $grid->column('address', '地理位置');
        $grid->column('created_at', '登陆时间');

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
        $show = new Show(LoginIpLog::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('ip', __('Ip'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LoginIpLog());

        $form->number('user_id', __('User id'));
        $form->ip('ip', __('Ip'));

        return $form;
    }
}
