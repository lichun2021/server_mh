<?php

namespace App\Admin\Controllers;

use App\Skins;
use App\SkinsLv;
use App\StarsList;
use App\StarsRecord;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StarsRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '红星轮盘参与记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StarsRecord());
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->column(1/3, function ($filter) {
                $filter->like('user.name', '用户');
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('order_id', '订单号');
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('stars_id', '红星')->select(StarsList::pluck('name','id')->toArray());
            });

        });
        $grid->model()->orderByDesc('id');
        $grid->column('id', __('Id'));
        $grid->column('user.name', '用户');
        $grid->column('stars.name', '红星')->sortable();
        $grid->column('bean', '消耗'.getConfig('bean_name'));
        $grid->column('skins.cover', '饰品图')->lightbox(['width' => 50]);
        $grid->column('skins.name', '饰品名称')->display(function (){
            if ($this->skins->dura === 0){
                return $this->skins->name;
            }
            return $this->skins->name .' ('. Skins::$fields['dura'][$this->skins->dura] .')';
        });
        $grid->column('skin_lv', '饰品等级')->using(SkinsLv::downList());
        $grid->column('skin_bean', '饰品价值');
        $grid->column('seat', '坐标号');
        $grid->column('order_id', '订单号')->sortable();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(StarsRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('stars_id', __('Stars id'));
        $show->field('order_id', __('Order id'));
        $show->field('seat', __('Seat'));
        $show->field('skin_id', __('Skin id'));
        $show->field('bean', __('Bean'));
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
        $form = new Form(new StarsRecord());

        $form->number('user_id', __('User id'));
        $form->number('stars_id', __('Stars id'));
        $form->text('order_id', __('Order id'));
        $form->switch('seat', __('Seat'));
        $form->number('skin_id', __('Skin id'));
        $form->decimal('bean', __('Bean'));

        return $form;
    }
}
