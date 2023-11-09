<?php

namespace App\Admin\Controllers;

use App\Bean;
use App\Box;
use App\Welfare;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;
use Encore\Admin\Controllers\AdminController;

class WelfareController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '福利活动';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Welfare());
        //禁用分页
        $grid->disablePagination();
        //禁用查询过滤器
        $grid->disableFilter();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
        });
        $grid->model()->orderBy('sort');

        $grid->column('id', __('Id'));
        $grid->column('name', __('名称'));
        $grid->column('box.cover', '宝箱封面')->image('', 75);
        $grid->column('box.name', __('宝箱名称'));
        $grid->column('type', __('类型'))->using(Welfare::$fields['type']);
        $grid->column('promotion_level', __('附加条件'))->display(function () {
            if ($this->type == 2) {
                return $this->promotion_level ?? '';
            } elseif ($this->type == 3) {
                return '$' . $this->promotion_level;
            } else {
                return '无';
            }
        });
        $grid->column('sort', '排序')->editable();
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('更新时间'));

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
        $show = new Show(Welfare::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('box_id', __('Box id'));
        $show->field('type', __('Type'));
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
        $form = new Form(new Welfare());
        $form->text('name', __('名称'))->required();
        $form->select('type', __('类型'))->options(Welfare::$fields['type'])->when(3, function (Form $form) {
            $form->radioCard('promotion_level', __('用户充值'))->options(Bean::getWelfareList());
        })->required();
        $form->select('box_id', __('宝箱'))->options(Box::orderBy('sort')->pluck('name', 'id'))->required();
        $form->number('sort', '排序');
        $form->textarea('description', '描述');
        $form->saving(function (Form $form) {
            if ($form->type == 1 && !$form->isEditing()) {
                $type_1 = Welfare::where('type', 1)->exists();
                if ($type_1) {
                    $error = new MessageBag([
                        'title' => '每日福利已存在',
                        'message' => '每日福利只允许设置一个箱子',
                    ]);
                    return back()->with(compact('error'));
                }
            } elseif ($form->type == 2) {
                $type_2 = Welfare::where(['type' => 2])->exists();
                if ($type_2 && !$form->isEditing()) {
                    $error = new MessageBag([
                        'title' => '累计充值福利已存在',
                        'message' => '累计充值福利已存在',
                    ]);
                    return back()->with(compact('error'));
                }
            } elseif ($form->type == 3) {
                $type_3 = Welfare::where(['type' => 3, 'promotion_level' => $form->promotion_level])->exists();
                if ($type_3 && !$form->isEditing() || !in_array($form->promotion_level, array_keys(Bean::getWelfareList()))) {
                    $error = new MessageBag([
                        'title' => '$' . $form->promotion_level . ' 充值福利已存在',
                        'message' => ' 充值$'.$form->promotion_level.'金额福利只允许设置一个箱子',
                    ]);
                    return back()->with(compact('error'));
                }
            }
        });
        return $form;
    }
}
