<?php

namespace App\Admin\Controllers;

use App\Box;
use App\BoxAward;
use App\AwardType;
use App\BoxLucky;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\MessageBag;

class BoxAwardController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '宝箱奖项';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BoxAward());

        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用分页
        //$grid->disablePagination();
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
        $box_id = request()->get('box_id', 0);
        $grid->model()->where('box_id', $box_id)->orderByDesc('bean');

        $user_awards = [];
        $anchor_awards = [];
        $user_luck_awards = [];
        $anchor_luck_awards = [];
        if (!empty($box_id) && $box_id > 0){
            $user_key = Box::$fields['cacheKey']['openBoxListId'] . $box_id;
            $anchor_key = Box::$fields['cacheKey']['openBoxListAnchorId'] . $box_id;
            $user_luck_key = Box::$fields['cacheKey']['openBoxLuckyListId'] . $box_id;
            $anchor_luck_key = Box::$fields['cacheKey']['openBoxLuckyListAnchorId'] . $box_id;
            $user_awards = Cache::get($user_key);
            $anchor_awards = Cache::get($anchor_key);
            $user_luck_awards = Cache::get($user_luck_key);
            $anchor_luck_awards = Cache::get($anchor_luck_key);
        }


        $grid->column('id', __('Id'));
        $grid->column('name', __('名称'));
        $grid->column('dura', __('耐久'))->using(BoxAward::$fields['dura']);
        $grid->column('lv', __('品质'))->using(BoxAward::$fields['lv']);
        $grid->column('cover', __('封面'))->image('', 75);
        $grid->column('odds', __('显示爆率'))->editable();
        $grid->column('real_odds', __('投放数量'))->editable();
        if ($user_awards){
            $grid->column('投放剩余')->display(function () use ($user_awards){
                $i = 0;
                foreach ($user_awards as $award_id){
                    if ($award_id === $this->id){
                        $i++;
                    }
                }
                return $i;
            });
        }
        $grid->column('anchor_odds', __('投放数量(主播)'))->editable();
        if ($anchor_awards){
            $grid->column('投放剩余(主播)')->display(function () use ($anchor_awards){
                $i = 0;
                foreach ($anchor_awards as $award_id){
                    if ($award_id === $this->id){
                        $i++;
                    }
                }
                return $i;
            });
        }
        $grid->column('luck_odds', __('投放数量(幸运)'))->editable();
        if ($user_luck_awards && $anchor_luck_awards){
            $grid->column('幸运剩余(用户/主播)')->display(function () use ($user_luck_awards, $anchor_luck_awards){
                $u = 0;
                foreach ($user_luck_awards as $award_id){
                    if ($award_id === $this->id){
                        $u++;
                    }
                }
                $a = 0;
                foreach ($anchor_luck_awards as $award_id){
                    if ($award_id === $this->id){
                        $a++;
                    }
                }
                return $u.'/'.$a;
            });
        }
        $grid->column('clear_lucky', __('幸运物品'))->editable('select', BoxAward::$fields['is_lucky']);
        $grid->column('bean', __('C币'))->editable();
        $grid->column('max_t', __('最大T币'))->editable();
        $grid->column('created_at', __('创建'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param integer $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(BoxAward::findOrFail($id));

        $show->field('id', __('Id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BoxAward());

        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });

        $form->text('name', __('名称'))->required();
        $form->select('type', __('类型'))->options(function () {
            $types = AwardType::get()->toArray();
            $typeArray = [];
            foreach ($types as $type) {
                $typeArray[$type['id']] = $type['name'];
            }
            return $typeArray;
        })->required();
        $form->select('dura', __('耐久'))->options(BoxAward::$fields['dura'])->required();
        $form->select('lv', __('品质'))->options(BoxAward::$fields['lv'])->required();
        $form->image('cover', __('封面'))->uniqueName();
        $form->number('odds', __('显示爆率'))->required()->rules(['integer', 'min:0'], ['integer' => '请输入整数', 'min' => '数值不能为负数']);
        $form->number('real_odds', __('投放数量'))->required()->rules(['integer', 'min:0'], ['integer' => '请输入整数', 'min' => '数值不能为负数']);
        $form->number('anchor_odds', __('投放数量(主播)'))->required()->rules(['integer', 'min:0'], ['integer' => '请输入整数', 'min' => '数值不能为负数']);
        $form->radioCard('clear_lucky', __('幸运物品'))->options(BoxAward::$fields['is_lucky'])->required()->help('选择是需要输入幸运物品投放数量')->when(1, function (Form $form) {
            $form->number('luck_odds', __('投放数量(幸运)'))->required()->rules(['integer', 'min:0'], ['integer' => '请输入整数', 'min' => '数值不能为负数']);
        });
        $form->radioCard('is_lucky_box', __('幸运开箱'))->options([0 => '否', 1 => '是'])->required()->help('幸运开箱物品，选择是 装备将出现在首页幸运开箱列表。');
        $form->currency('bean', __('R币'))->symbol('R')->required();
        $form->currency('max_t', __('最高T币'))->symbol('T')->help('用户取回的时候最高T币,暂时保留，后期可能废弃此输入框！');
        //保存后回调
        $form->saved(function (Form $form) {
            try {
                /*
                $box_luck = BoxLucky::where('box_id',$form->model()->box_id)->first();
                if ($box_luck){
                    $box_luck->luck_value = $form->
                }*/
                //缓存原子锁
                $lockKey = Box::$fields['cacheKey']['openBoxAnchorLockId'] . $form->model()->box_id;
                $lockAnchorKey = Box::$fields['cacheKey']['openBoxLockId'] . $form->model()->box_id;
                //缓存KEY
                $user_key = Box::$fields['cacheKey']['openBoxListId'] . $form->model()->box_id;
                $anchor_key = Box::$fields['cacheKey']['openBoxListAnchorId'] . $form->model()->box_id;
                $user_luck_key = Box::$fields['cacheKey']['openBoxLuckyListId'] . $form->model()->box_id;
                $anchor_luck_key = Box::$fields['cacheKey']['openBoxLuckyListAnchorId'] . $form->model()->box_id;
                //宝箱详情缓存
                $box_detail_key = 'box_detail_'.$form->model()->box_id;
                //定义锁
                $cacheLock = Cache::lock($lockKey, 10);
                $cacheAnchorLock = Cache::lock($lockAnchorKey, 10);
                //拿锁
                $cacheLock->block(10);
                $cacheAnchorLock->block(10);
                //清缓存
                Cache::delete($user_key);
                Cache::delete($anchor_key);
                Cache::delete($user_luck_key);
                Cache::delete($anchor_luck_key);
                Cache::delete($box_detail_key);
                //释放锁
                $cacheLock->release();
                $cacheAnchorLock->release();
            } catch (\Exception $e) {
                //释放锁
                $cacheLock->release();
                $cacheAnchorLock->release();
                throw new \Exception("无法拿到缓存原子锁！", -1);
            }
        });
        return $form;
    }
}
