<?php

namespace App\Admin\Controllers;

use App\SteamItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SteamItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Steam';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SteamItem());
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->like('item_name', '名称');
        });

        $grid->column('id', __('Id'));
        $grid->column('app_id', __('AppId'));
        $grid->column('item_name', __('名称'));
        $grid->column('market_hash_name', __('唯一表示'));
        $grid->column('image_url', __('封面'))->image('', 75);
        $grid->column('bean', __('C币'))->editable();
        $grid->column('max_t', __('最大T豆'))->editable();
        $grid->column('updated_at', __('最后一次更新时间'));
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
        $show = new Show(SteamItem::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('app_id', __('App id'));
        $show->field('item_name', __('Item name'));
        $show->field('market_hash_name', __('Market hash name'));
        $show->field('short_name', __('Short name'));
        $show->field('image_url', __('Image url'));
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
        $form = new Form(new SteamItem());
        $form->number('app_id',__('App id'));
        $form->text('item_name',__('名称'));
        $form->text('market_hash_name',__('唯一表示'));
        $form->text('short_name',__('中文名称'));
        $form->text('image_url',__('封面'));
        $form->currency('bean', __('C币'))->symbol('M');
        $form->currency('max_t', __('最高T币'))->symbol('T')->help('用户取回的时候最高T币');

        return $form;
    }
}
