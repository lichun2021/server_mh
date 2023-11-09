<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/11/17 15:18
 */

namespace App\Admin\Controllers;

use App\Box;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Admin\Actions\Game\BoxAward;
use App\Admin\Actions\Game\AddAward;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Database\Eloquent\Collection;

class GameBoxController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '对战宝箱';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Box());
        $grid->model()->collection(function (Collection $collection) {
            foreach ($collection as $item) {
                $contains = \App\BoxContain::with(['skins:id,bean,dura'])
                    ->select(['skin_id', 'game_odds'])
                    ->where('box_id', $item->id)
                    ->get()
                    ->toArray();
                $prizePool = 0;
                $real_odds = 0;
                foreach ($contains as $contain) {
                    if ($contain['game_odds'] > 0) {
                        $prizePool += $contain['game_odds'] * $contain['skins']['bean'];
                        $real_odds += $contain['game_odds'];
                    }
                }
                $costPerRound = bcmul($item->game_bean, $real_odds, 2);
                $profit = $costPerRound - $prizePool;
                $profitMargin = $profit <= 0 ? 0 : bcmul($profit / $costPerRound, 100, 2);
                $item->cost_per_round = $costPerRound;
                $item->prize_pool = $prizePool;
                $item->profit = $profit;
                $item->profit_margin = $profitMargin;
            }
            return $collection;
        })->where('type',1);
        //禁用创建按钮
        //$grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->actions(function ($actions) {
            //去掉操作
            //$actions->disableDelete();
            $actions->disableView();
            $actions->add(new AddAward());
            $actions->add(new BoxAward());
        });

        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            //$filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('name', __('Name'));
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('is_game', '上架')->select([0 => '否', 1 => '是']);
            });

        });

        $grid->model()->orderByDesc('is_game')->orderBy('sort');
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];
        $grid->column('id', 'Id');
        $grid->column('name', __('Name'));
        $grid->column('intact_cover', '对战宝箱封面')->lightbox(['width' => 50]);
        $grid->column('game_bean', getConfig('bean_name'))->editable();
        $grid->column('cost_per_round', '每轮花费');
        $grid->column('prize_pool', '奖池总价');
        $grid->column('profit', '每轮盈利');
        $grid->column('profit_margin', '利润率(%)')->display(function () {
            if ($this->profit_margin <= 10) {
                return '<span class="label label-danger">' . $this->profit_margin . '</span>';
            } else {
                return '<span class="label label-success">' . $this->profit_margin . '</span>';
            }
        });
        $grid->column('is_game', '是否上架')->switch($states);
        $grid->column('sort', __('Sort'))->editable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Box());

        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];
        $form->text('name', __('Name'))->required();
        $form->image('intact_cover', '完整封面')->required()->uniqueName()->help('用于盲盒对战宝箱完整图片')->move('images/box');
        $form->number('sort', __('Sort'))->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:4294967295',
        ],[
            'required' => '请输入排序',
            'integer' => '排序必须为整数',
            'min' => '排序最小值支持0 最大值支持 4294967295',
            'max' => '排序最小值支持0 最大值支持 4294967295',
        ]);
        $form->currency('game_bean', getConfig('bean_name'))->symbol('M');
        $form->switch('is_game', '上架')->states($states);
        $form->hidden('type')->default(1);
        if ($form->isCreating()){
            $form->hidden('luck_interval');
            $form->hidden('luck_interval_anchor');
            //保存前
            $form->saving(function (Form $form) {
                $form->luck_interval = ["100", "500"];
                $form->luck_interval_anchor = ["100", "500"];
            });
        }
        //保存后回调
        $form->saved(function (Form $form) {
            //清除宝箱下拉缓存
            \Cache::delete(Box::$fields['cacheKey'][0]);
            //清理对战宝箱列表缓存
            \Cache::delete(Box::$fields['cacheKey'][9]);
            //清理宝箱详情缓存
            \Cache::delete(Box::$fields['cacheKey'][8].$form->model()->id);
        });
        return $form;
    }
}
