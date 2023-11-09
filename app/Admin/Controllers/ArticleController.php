<?php

namespace App\Admin\Controllers;

use App\Article;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ArticleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '文章';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        //关闭PJAX
        request()->headers->set('X-PJAX', false);

        $grid = new Grid(new Article());
        //禁用导出
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //过滤器
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->column(1 / 2, function ($filter) {
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->like('title', '标题');
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->column('id', 'Id');
        $grid->column('title', '标题');
        $grid->column('type', '类型')->using(Article::$fields['type']);
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
        $show = new Show(Article::findOrFail($id));

        $show->field('id', 'Id');
        $show->field('title', '标题');
        $show->field('content', '内容')->as(function ($content) {
            return "<pre>{$content}</pre>";
        });
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
        $form = new Form(new Article());
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });
        $form->text('title', '标题')->rules(['required'], [
            'required' => '请输入标题'
        ]);
        $form->select('type', '类型')->options(Article::$fields['type'])->rules(['required'], [
                'required' => '请输选择类型'
            ]);
        $form->tinymce('content', '内容');

        return $form;
    }
}
