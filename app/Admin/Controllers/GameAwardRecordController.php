<?php

namespace App\Admin\Controllers;

use App\GameAwardRecord;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class GameAwardRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'GameAwardRecord';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GameAwardRecord());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用操作
        $grid->disableActions();

        $grid->column('id', 'Id');
        $grid->column('game_arena_id', '对战Id')->link(function (){
            return '/'.config('admin.route.prefix').'/game-arenas?id='.$this->game_arena_id;
        },false);
        $grid->column('user.name', '用户');
        $grid->column('box.name', '宝箱');
        $grid->column('box.intact_cover', '宝箱封面')->image('',75);
        $grid->column('box_bean', '宝箱价格');
        $grid->column('skins.name', '饰品名');
        $grid->column('skins.cover', '饰品封面')->image('',75);
        $grid->column('award_bean', '饰品价值');
        $grid->column('status', '状态')->using([0 => '等待中', 1 => '已开奖']);
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
        $show = new Show(GameAwardRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('game_arena_id', __('Game arena id'));
        $show->field('user_id', __('User id'));
        $show->field('box_id', __('Box id'));
        $show->field('box_bean', __('Box bean'));
        $show->field('award_id', __('Award id'));
        $show->field('award_bean', __('Award bean'));
        $show->field('status', __('Status'));
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
        $form = new Form(new GameAwardRecord());

        $form->number('game_arena_id', __('Game arena id'));
        $form->number('user_id', __('User id'));
        $form->number('box_id', __('Box id'));
        $form->decimal('box_bean', __('Box bean'))->default(0.00);
        $form->number('award_id', __('Award id'));
        $form->decimal('award_bean', __('Award bean'))->default(0.00);
        $form->switch('status', __('Status'));

        return $form;
    }
}
