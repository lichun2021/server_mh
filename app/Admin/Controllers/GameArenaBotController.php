<?php

namespace App\Admin\Controllers;

use App\Box;
use App\BoxContain;
use App\GameArena;
use App\GameArenaBot;
use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\Collection;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class GameArenaBotController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '对机器人房间';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GameArenaBot());
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('user_num', '对战模式')->select(GameArena::$fields['user_num']);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('status', '状态')->select([0=> '禁用',1=>'启用']);
            });
        });
        $grid->model()->orderByDesc('id');
        $grid->model()->collection(function (Collection $collection) {
            foreach ($collection as $item){
                $urls = [];
                $check_status = 1;
                $check_msg = null;
                $total_bean = 0;
                if (empty($item->boxs)){
                    $check_status = 0;
                    $check_msg = '未选择对战宝箱';
                }
                foreach ($item->boxs as $box){
                    $boxInfo = Box::where(['id' => $box['box_id']])->first();

                    if (!$boxInfo || $boxInfo->is_game === 0){
                        $check_msg = '对战宝箱被删除或已下架！';
                        $check_status = 0;
                    }
                    if ($boxInfo){
                        $total_bean += $boxInfo->game_bean;
                        $urls[] = $boxInfo->intact_cover;
                    }
                    $game_odds = BoxContain::query()->where(['box_id' => $box['box_id'], 'is_game' => 1])->sum('game_odds');
                    $game_anchor_odds = BoxContain::query()->where(['box_id' => $box['box_id'], 'is_game' => 1])->sum('game_anchor_odds');
                    if ($game_odds < 1 || $game_anchor_odds < 1){
                        $check_msg = '对战宝箱用户或主播爆率未设置！';
                        $check_status = 0;
                    }

                }
                $item->boxs = $urls;
                $item->check_status = $check_status;
                $item->check_msg = $check_msg;
                $item->total_bean = $total_bean;
            }
            // 最后一定要返回集合对象
            return $collection;
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->column('id', 'Id');
        $grid->column('user_num', '对战模式')->using(GameArena::$fields['user_num']);
        $grid->column('boxs', '宝箱数据')->lightbox(['height' => 50]);
        $grid->column('total_bean', getConfig('bean_name'));
        $grid->column('check_status', '自检状态')->using(GameArenaBot::$fields['check_status'])->label([
            0 => 'danger',
            1 => 'success'
        ]);
        $grid->column('check_msg', '自检错误');
        $states = [
            'on'  => ['value' => 1, 'text' => '启用', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'danger'],
        ];
        $grid->column('status', '状态')->switch($states);
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
        $show = new Show(GameArenaBot::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_num', __('User num'));
        $show->field('boxs', __('Boxs'));
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
        $form = new Form(new GameArenaBot());
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();

        });

        $form->select('user_num', '对战模式')->options(GameArena::$fields['user_num'])->rules('required',[
            'required' => '请选择对战模式'
        ]);
        $form->table('boxs','对战宝箱', function ($table) {
            $table->select('box_id','宝箱')->options(GameArenaBot::getBoxList())->rules('required',[
                'required' => '请输选择宝箱'
            ]);
        });
        $states = [
            'on'  => ['value' => 1, 'text' => '启用', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'danger'],
        ];
        $form->switch('status', '状态')->states($states)->default(1);
        //$form->number()->default()->rules()
        $form->saving(function (Form $form) {
            try {
                if (request()->post('_previous_') !== null && $form->boxs === null){
                    throw new \Exception('至少添加1个对战宝箱');
                }
                if (request()->post('_previous_') !== null && count($form->boxs) > 6){
                    throw new \Exception('最大添支持6个对战宝箱');
                }
            } catch (\Exception $e){
                $error = new MessageBag([
                    'title'   => '错误',
                    'message' => $e->getMessage(),
                ]);
                return back()->with(compact('error'));
            }
        });
        return $form;
    }
}
