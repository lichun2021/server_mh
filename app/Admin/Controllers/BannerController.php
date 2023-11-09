<?php

namespace App\Admin\Controllers;

use App\Banner;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\AdminController;

class BannerController extends AdminController
{

    public static $states = [
        'on'  => ['value' => 1, 'text' => '打开', 'color' => 'success'],
        'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
    ];

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Banner';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Banner());
        //禁用导出
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用操作
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('title', '标题');
        });

        $grid->model()->orderBy('sort')->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('title', '标题');
        $grid->column('image', '图片')->image('',500);
        $grid->column('href', '链接')->editable();
        $grid->column('sort', '排序')->editable();
        $grid->column('status', '状态')->switch(self::$states);
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Banner());
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });
        $form->text('title', '标题')->rules(['required'],[
            'required' => '请输入标题'
        ]);
        $form->image('image', '图片')->rules(['required'],[
            'required' => '请输选择图片'
        ])->uniqueName()->move('images/banners');
        $form->url('href', '链接')->rules(['url'],[
            'url' => '输入链接错误，不是有效的链接格式。'
        ]);
        $form->number('sort', '排序')->rules(['required'],[
            'required' => '请输入排序'
        ])->default(0);
        $form->switch('status', '状态')->states(self::$states);

        $form->saved(function () {
            $key = Banner::$fields['cacheKey'];
            \Cache::delete($key);
        });
        return $form;
    }
}
