<?php

namespace App\Admin\Controllers;

use App\Box;
use App\Skins;
use App\BoxRecord;
use App\BoxAward;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BoxRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '开箱记录';

    public function __construct()
    {
        if (request()->get('user_id')){
            $this->title = '仓库武器';
        }
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BoxRecord());

        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
                $filter->like('name', '饰品名称');
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('user.name', '用户');
                $filter->equal('status', '状态')->select(BoxRecord::$fields['status']);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('user.mobile', '用户手机')->mobile();
                $filter->equal('box_id', '宝箱')->select(Box::getList());
            });
        });
        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            //$actions->disableEdit();
            $actions->disableView();
        });

        if (request()->get('user_id')){
            $grid->model()->where('user_id',request()->get('user_id'))->where('status',0)->orderBy('id', 'DESC');
        }else{
            $grid->model()->orderBy('id', 'DESC');
        }


        $grid->column('id', __('Id'));
        $grid->column('get_user.name', '获得者');
        $grid->column('user.name', '持有者');
        $grid->column('box_name', '宝箱名称');
        $grid->column('box_bean', '消耗'.getConfig('bean_name'));
        $grid->column('name', '饰品名称');
        $grid->column('cover', '饰品封面')->lightbox(['width' => 75]);
        $grid->column('dura', '饰品外观')->using(Skins::$fields['dura']);
        $grid->column('bean', '饰品价值');
        $grid->column('status', __('Status'))->using(BoxRecord::$fields['status'])->dot([
            0 => 'primary',
            1 => 'success',
            2 => 'warning',
            3 => 'info',
            4 => 'danger',
            5 => 'warning',
            6 => 'info',
        ], 'warning');
        $grid->column('back_message', '回调消息');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

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
        $show = new Show(BoxRecord::findOrFail($id));

        $show->field('id', 'Id');
        $show->field('get_user.name', '获得者');
        $show->field('user.name', '持有者');
        $show->field('box_id', '宝箱')->using(Box::getList());
        $show->field('box_name', '宝箱名称');
        $show->field('box_bean', '消耗'.getConfig('bean_name'));
        $show->field('name', '饰品名称');
        $show->field('bean', '饰品价值');
        $show->field('cover', '饰品封面');
        $show->field('dura', '饰品外观');
        $show->field('lv', '饰品等级');
        $show->field('status', 'Status')->using(BoxRecord::$fields['status']);
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
        $form = new Form(new BoxRecord());

        $form->text('user_id', '持有者Id')->disable();
        $form->text('box_id','宝箱Id')->disable();
        $form->text('box_name','宝箱名称')->disable();
        $form->number('box_bean', '消耗'.getConfig('bean_name'))->disable();
        $form->text('name', '饰品名称');
        $form->image('cover', '饰品封面')->disable();
        $form->select('dura','饰品外观')->options(Skins::$fields['dura'])->required();
        $form->select('lv', '饰品等级')->options(Skins::$fields['lv'])->required();
        return $form;
    }
}
