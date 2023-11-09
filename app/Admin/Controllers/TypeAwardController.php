<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/26
 * Time: 22:10
 */

namespace App\Admin\Controllers;

use App\BoxAward;
use App\AwardType;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Cache;

class TypeAwardController extends AdminController
{
    protected $title = '装备分类';

    protected function grid()
    {
        $grid = new Grid(new BoxAward());

        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用查询过滤器
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->disableIdFilter();
            $filter->like('name', '名称');
        });
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用所有操作
        $grid->disableActions();
        $grid->column('id', 'Id');
        $grid->column('cover', __('封面'))->image('', 75);
        $grid->column('name', __('名称'));
        $grid->column('dura', __('耐久'))->using(BoxAward::$fields['dura']);
        $grid->column('box.name', __('所在宝箱'));
        $grid->column('type', __('类型'))->editable('select',AwardType::downList());
        $grid->column('luck_interval', '幸运区间')->editable();
        $grid->column('luck_interval_anchor', '幸运区间(主播)')->editable();
        $grid->column('剩余额度')->display(function () {
            $luk = Cache::get('lucky_open_box_cache_v2_'.$this->id);
            return $luk;
        })->help('还需要最低消费多少额度爆出该武器。');
        $grid->column('luk', '剩余额度(主播)')->display(function () {
            $luk = Cache::get('lucky_open_box_cache_anchor_v2_'.$this->id);
            return $luk;
        })->help('还需要最低消费多少额度爆出该武器。');
        $grid->column('is_lucky_box', __('幸运开箱'))->editable('select',[0=>'否',1=>'是'])->sortable();
        $grid->column('bean', __('C币'))->editable();
        $grid->column('lv', __('品质'))->using(BoxAward::$fields['lv']);

        return $grid;
    }

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
        $form->image('cover', __('封面'))->uniqueName();
        $form->number('odds', __('显示爆率'))->required();
        $form->number('real_odds', __('真实爆率'))->required();
        $form->radioCard('clear_lucky', __('清幸运值物品'))->options(BoxAward::$fields['is_lucky'])->required();
        $form->select('dura', __('耐久'))->options(BoxAward::$fields['dura'])->required();
        $form->select('lv', __('品质'))->options(BoxAward::$fields['lv'])->required();
        $form->select('is_lucky_box', __('幸运开箱'))->options([0=>'否',1=>'是'])->required()->help('幸运开箱物品，选择是 装备将出现在首页幸运开箱列表。');
        $form->text('luck_interval', __('幸运区间'))->rules(['required','regex:/^[0-9]+\/[0-9]+$/'],[
            'required' => '区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 110/200'
        ])->help('区间开始结束值 如：110/200 110为开始值 200为结束值，结束值必须大于起始值。');
        $form->text('luck_interval_anchor', __('幸运区间(主播)'))->rules(['required','regex:/^[0-9]+\/[0-9]+$/'],[
            'required' => '主播区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 110/200'
        ])->help('主播幸运区间开始结束值 如：110/200 110为开始值 200为结束值，结束值必须大于起始值。');
        $form->select('type', __('类型'))->options(function () {
            return AwardType::downList();
        })->required();
        $form->currency('bean', __('C币'))->symbol('C')->required();
        $form->currency('max_t', __('最高T币'))->symbol('T')->help('用户取回的时候最高T币');
        //保存前回调
        $form->saving(function (Form $form) {
            try{
                if (count($interval =  explode('/',trim($form->luck_interval))) == 2){
                    if ($interval[0] >= $interval[1]){
                        throw new \Exception('结束值必须大于起始值');
                    }
                    $form->luck_interval = $interval;
                }
                if (count($interval_anchor =  explode('/',trim($form->luck_interval_anchor))) == 2){
                    if ($interval_anchor[0] >= $interval_anchor[1]){
                        throw new \Exception('结束值必须大于起始值');
                    }
                    $form->luck_interval_anchor = $interval_anchor;
                }
                if ($form->is_lucky_box == 1){
                    if (empty($form->model()->luck_interval) || empty($form->model()->luck_interval_anchor)){
                        throw new \Exception('请先设置饰品区间值！');
                    }
                }
            } catch (\Exception $e){
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ]);
            }
        });

        return $form;
    }
}
