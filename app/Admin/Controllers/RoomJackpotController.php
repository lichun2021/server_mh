<?php

namespace App\Admin\Controllers;

use App\RoomJackpot;
use App\Admin\Actions\Room\JackpotAddAward;
use App\Admin\Actions\Room\JackpotAwards;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RoomJackpotController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Roll房奖池';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RoomJackpot());

        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->disableIdFilter();
            $filter->like('name', '奖池名称');
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->add(new JackpotAddAward());
            $actions->add(new JackpotAwards());
        });

        $grid->column('id', __('Id'));
        $grid->column('name', '奖池名称');
        $grid->column('explain', '奖池说明');
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
        $show = new Show(RoomJackpot::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', '奖池名');
        $show->field('explain', '奖池说明');
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
        $form = new Form(new RoomJackpot());

        $form->text('name', '奖池名称')->rules('required|max:60',[
            'required' => '请输入奖池名',
            'max' => '奖池名最多支持60个字',
        ]);
        $form->textarea('explain', '奖池说明');

        return $form;
    }
}
