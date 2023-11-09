<?php

namespace App\Admin\Controllers;

use App\SkinsType;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Controllers\AdminController;

/**
 * 饰品类型管理
 * Class SkinsTypeController
 * @package App\Admin\Controllers
 * @author 春风 <860646000@qq.com>
 */
class SkinsTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '饰品类型';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SkinsType());
        //禁用分页
        $grid->disablePagination();
        //禁用查询过滤器
        $grid->disableFilter();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();

        $grid->model()->orderBy('sort');
        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->editable();
        $grid->column('cover', __('Cover'))->image('',75);
        $grid->column('sort', __('Sort'))->editable();

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
        $show = new Show(SkinsType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('cover', __('Cover'))->image('',100);
        $show->field('sort', __('Sort'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SkinsType());

        $form->text('name', __('Name'))->required();
        $form->image('cover', __('Cover'))->uniqueName()->required()->move('images/type');
        $form->number('sort', __('Sort'))->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:255',
        ],[
            'required' => '请输入排序',
            'integer' => '排序必须为整数',
            'min' => '排序最小值支持0 最大值支持 255',
            'max' => '排序最小值支持0 最大值支持 255',
        ]);
        //保存后执行
        $form->saved(function (Form $form) {
            //清除类型缓存
            Cache::delete(SkinsType::$fields['cacheKey'][0]);
            //清除幸运开箱分类缓存
            Cache::delete(SkinsType::$fields['cacheKey'][1]);
        });
        return $form;
    }
}
