<?php

namespace App\Admin\Controllers;

use App\GameArenaUser;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class GameArenaUserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '对战玩家';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GameArenaUser());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用操作
        $grid->disableActions();
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->disableIdFilter();
            $filter->equal('game_arena_id', '对战Id');
        });
        if (request()->get('game_arena_id')){
            $grid->model()->where('game_arena_id',request()->get('game_arena_id'))->orderByDesc('game_arena_id')->orderBy('seat');
        } else {
            $grid->model()->orderByDesc('game_arena_id')->orderBy('seat');
        }

        $grid->column('id', 'Id');
        $grid->column('game_arena_id', '对战Id');
        $grid->column('user.name', '用户');
        $grid->column('seat', '座位号');
        $grid->column('worth', '饰品价值')->modal('对战宝箱', function ($model) {
            $headers = ['Id', '用户', '宝箱', '宝箱封面', '宝箱价格', '饰品', '饰品封面', '饰品价值'];
            $rows = [];
            foreach ($model->game_award->where('game_arena_id',$this->game_arena_id) as $award) {
                $rows[] = [
                    $award->id,
                    $award->user->name ?? null,
                    $award->box->name ?? null,
                    !empty($award->box->intact_cover) ? '<img src="'. $award->box->intact_cover .'" width="75px">':null,
                    $award->box_bean,
                    $award->skins->name ?? null,
                    !empty($award->skins->cover) ? '<img src="'. $award->skins->cover .'" width="75px">':null,
                    $award->award_bean
                ];
            }
            return new Table($headers, $rows);
        });;
        $grid->column('win_worth', '赢得价值');
        $grid->column('is_win', '赢家')->using([0 => '否', 1 => '是'])->dot([
            0 => 'danger',
            1 => 'success'
        ]);
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
        $show = new Show(GameArenaUser::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('game_arena_id', __('Game arena id'));
        $show->field('user_id', __('User id'));
        $show->field('seat', __('Seat'));
        $show->field('worth', __('Worth'));
        $show->field('win_worth', __('Win worth'));
        $show->field('is_win', __('Is win'));
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
        $form = new Form(new GameArenaUser());

        $form->number('game_arena_id', __('Game arena id'));
        $form->number('user_id', __('User id'));
        $form->switch('seat', __('Seat'));
        $form->decimal('worth', __('Worth'))->default(0.00);
        $form->decimal('win_worth', __('Win worth'))->default(0.00);
        $form->switch('is_win', __('Is win'));

        return $form;
    }
}
