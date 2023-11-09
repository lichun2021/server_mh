<?php

namespace App\Admin\Controllers;

use App\Box;
use App\BoxCate;
use App\BoxLucky;
use App\Admin\Actions\Box\AddContain;
use App\Admin\Actions\Box\BoxContains;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Database\Eloquent\Collection;

class BoxController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '宝箱';

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
                    ->select(['skin_id', 'real_odds'])
                    ->where('box_id', $item->id)
                    ->get()
                    ->toArray();
                $prizePool = 0;
                $real_odds = 0;
                foreach ($contains as $contain) {
                    if ($contain['real_odds'] > 0) {
                        $prizePool += $contain['real_odds'] * $contain['skins']['bean'];
                        $real_odds += $contain['real_odds'];
                    }
                }
                $costPerRound = bcmul($item->bean, $real_odds, 2);
                $profit = $costPerRound - $prizePool;
                $profitMargin = $profit <= 0 ? 0 : bcmul($profit / $costPerRound, 100, 2);
                $item->cost_per_round = $costPerRound;
                $item->prize_pool = $prizePool;
                $item->profit = $profit;
                $item->profit_margin = $profitMargin;
            }
            return $collection;
        })->where('type',0);

        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            //$filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1 / 3, function ($filter) {
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->like('name', __('Name'));
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->equal('is_putaway', '上架')->select([0 => '否', 1 => '是']);
            });

        });
        $grid->actions(function ($actions) {
            $actions->add(new BoxContains());
            $actions->add(new AddContain());
            //去掉操作
            $actions->disableView();
        });

        $grid->model()->orderBy('sort');

        $grid->column('id', 'Id');
        $grid->column('name', __('Name'))->modal('奖品', function ($model) {
            $headers = ['Id', '名称', '颜色', getConfig('bean_name'), '封面', '显示爆率', '投放数量', '投放数量(主播)', '幸运物品', '投放数量(幸运)', '操作'];
            $rows = [];
            foreach ($model->contains as $contain) {
                $rows[] = [
                    $contain->id,
                    $contain->skins->name . ' (' . $contain->skins->dura_alias . ')',
                    $contain->level_name,
                    $contain->skins->bean,
                    '<img src="' . $contain->skins->cover . '" width="50px">',
                    $contain->odds,
                    $contain->real_odds,
                    $contain->anchor_odds,
                    $contain::$fields['is_luck'][$contain->is_luck],
                    $contain->luck_odds,
                    '<a style="cursor:pointer" onclick="window.location.href=\'/' . config('admin.route.prefix') . '/box-contains?box_id=' . $contain->box_id . '\'">编辑</a>'
                ];
            }
            return new Table($headers, $rows);
        });
        $grid->column('cover', '封面')->lightbox(['width' => 50]);
        $grid->column('cate_id', '所属分类')->editable('select', BoxCate::getList());
        $grid->column('sort', '排序')->editable();
        $grid->column('luck_interval', '幸运区间')->editable();
        $grid->column('luck_interval_anchor', '幸运区间(主播)')->editable();
        $grid->column('bean', getConfig('bean_name'))->editable();
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

        $grid->column('is_putaway', '是否上架')->editable('select', Box::$fields['is_putaway']);

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

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();

        });

        $form->footer(function ($footer) {
            //去掉`查看`checkbox
            $footer->disableViewCheck();
        });

        $form->select('cate_id', '宝箱分类')->options(BoxCate::getList())->required();
        $form->text('name', __('Name'))->rules(['required', 'string', 'max:16'],[
            'required' => '请输入名称',
            'string' => '名称验证失败',
            'max' => '名称最多支持16个字符',
        ]);
        $form->image('cover', '宝箱封面')->required()->uniqueName()->help('宝箱外观图片')->move('images/box');
        $form->image('weapon_cover', '武器封面')->required()->uniqueName()->help('宝箱展示的武器图片')->move('images/box');
        //$form->image('intact_cover', '完整封面')->required()->uniqueName()->help('宝箱完整图片')->move('images/box');
        $form->number('sort', __('Sort'))->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:4294967295',
        ], [
            'required' => '请输入排序',
            'integer' => '排序必须为整数',
            'min' => '排序最小值支持0 最大值支持 4294967295',
            'max' => '排序最小值支持0 最大值支持 4294967295',
        ]);

        $form->text('luck_interval', __('幸运区间'))->rules(['required', 'regex:/^[0-9]+\/[0-9]+$/'], [
            'required' => '幸运区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 10/20'
        ])->help('幸运区间开始结束值 如：10/20 10为开始值 20为结束值，结束值必须大于起始值。')->default('1/5');
        $form->text('luck_interval_anchor', __('幸运区间(主播)'))->rules(['required', 'regex:/^[0-9]+\/[0-9]+$/'], [
            'required' => '幸运区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 10/20'
        ])->help('主播幸运区间开始结束值 如：10/20 10为开始值 20为结束值，结束值必须大于起始值。')->default('1/5');
        $form->currency('bean', getConfig('bean_name'))->required()->symbol('M');
        //$form->currency('game_bean', getConfig('bean_name').'（对战）')->default(0)->symbol('M');
        $states = [
            'on' => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];
        $form->switch('is_putaway', '上架')->states($states);
        //$form->switch('is_game', '上架(对战)')->states($states);
        $form->hidden('type')->default(0);
        //保存前回调
        $form->saving(function (Form $form) {
            $interval = explode('/', trim($form->luck_interval));
            if (count($interval) == 2) {
                if ($interval[0] >= $interval[1]) {
                    throw new \Exception('结束值必须大于起始值');
                }
                $form->luck_interval = $interval;
            }

            $interval_anchor = explode('/', trim($form->luck_interval_anchor));
            if (empty($interval_anchor)) {
                throw new \Exception('配置错误，请检查！');
            }
            if (count($interval_anchor) == 2) {
                if ($interval_anchor[0] >= $interval_anchor[1]) {
                    throw new \Exception('结束值必须大于起始值');
                }
                $form->luck_interval_anchor = $interval_anchor;
            }
        });

        //保存后回调
        $form->saved(function (Form $form) {
            //重新生成幸运值
            $box_luck = BoxLucky::where('box_id', $form->model()->id)->first();
            if ($box_luck && ($form->luck_interval || $form->luck_interval_anchor)) {
                $box_luck->luck_value = $form->model()->luck_interval;
                $box_luck->luck_anchor_value = $form->model()->luck_interval_anchor;
                $box_luck->save();
            }
            //清除下拉缓存
            \Cache::delete(Box::$fields['cacheKey'][0]);
            //清理宝箱列表缓存
            \Cache::delete(Box::$fields['cacheKey'][1]);
            //清理对战宝箱列表缓存
            \Cache::delete(Box::$fields['cacheKey'][9]);
            //清理宝箱详情缓存
            \Cache::delete(Box::$fields['cacheKey'][8] . $form->model()->id);
        });

        return $form;
    }
}
