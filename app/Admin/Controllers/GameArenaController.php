<?php

namespace App\Admin\Controllers;

use App\User;
use App\GameArena;
use App\Admin\Actions\Game\ToUserList;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class GameArenaController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '对战';
    
    protected $description = [
        'index'  => '记录'
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GameArena());
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        //操作
        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableEdit();
            $actions->disableView();
            $actions->disableDelete();
            $actions->add(new ToUserList());
        });

        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            //$filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/2, function ($filter) {
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('game_arena_player.user.mobile', '玩家手机号');
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('draw_code', '开奖编号');
            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('status', '状态')->select([0 => '等待中', 2 => '已结束']);
            });
        });
        $grid->model()->orderBy('status')->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('create_user.name', '创建用户');
        $grid->column('user_num','对战模式')->display(function (){
            $num = $this->user_num;
            if ($num == 2){
                return '双人对战';
            } else if ($num == 3){
                return '三人对战';
            } else if ($num == 4){
                return '四人对战';
            }
        });
        $grid->column('box_num', '宝箱数量')->modal('对战宝箱', function ($model) {
            $headers = ['Id', '宝箱封面', '名称', getConfig('bean_name')];
            $rows = [];
            foreach ($model->game_arena_box as $arena_box) {
                $rows[] = [
                    $arena_box->id,
                    !empty($arena_box->box->intact_cover) ? '<img src="'. $arena_box->box->intact_cover .'" width="50px">':null,
                    $arena_box->box->name ?? null,
                    $arena_box->box_bean,
                ];
            }
            return new Table($headers, $rows);
        });
        $grid->column('total_bean', getConfig('bean_name').'/人');
        $grid->column('status', '状态')->using(GameArena::$fields['status'])->dot([
            0 => 'success',
            2 => 'danger'
        ]);
        $grid->column('win_user_id', '赢家')->display(function (){
            if ($this->win_user_id !== null){
                //var_dump($this->win_user_id);die;
                $users = User::query()->whereIn('id',$this->win_user_id)->get()->toArray();
                $user_name = [];
                foreach ($users as $user){
                    $user_name[] = '<a href="/'.config('admin.route.prefix').'/users?id='.$user['id'].'" target="_blank">'.$user['name'].'</a>';
                }
                return implode('<br>',$user_name);
            }
            return null;
        });
        $grid->column('draw_code', '开奖编号');
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
        $show = new Show(GameArena::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('create_user_id', __('Create user id'));
        $show->field('user_num', __('User num'));
        $show->field('box_num', __('Box num'));
        $show->field('total_bean', __('Total bean'));
        $show->field('status', __('Status'));
        $show->field('win_user_id', __('Win user id'));
        $show->field('draw_code', __('Draw code'));
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
        $form = new Form(new GameArena());

        $form->number('create_user_id', __('Create user id'));
        $form->switch('user_num', __('User num'));
        $form->switch('box_num', __('Box num'));
        $form->decimal('total_bean', __('Total bean'))->default(0.00);
        $form->switch('status', __('Status'));
        $form->text('win_user_id', __('Win user id'));
        $form->text('draw_code', __('Draw code'));

        return $form;
    }
}
