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

use App\Skins;
use App\SkinsType;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Cache;
use App\Admin\Actions\LuckySkins\UpperShelf;
use App\Admin\Actions\LuckySkins\LowerShelf;

class LuckySkinsController extends AdminController
{
    protected $title = '幸运饰品';

    protected function grid()
    {
        $grid = new Grid(new Skins());

        //禁用创建按钮
        $grid->disableCreateButton();
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            $batch->add(new UpperShelf());
            $batch->add(new LowerShelf());
        });
        $grid->filter(function ($filter) {
            $filter->column(1/3, function ($filter) {
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('name', __('Name'));
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('type', '类型')->select(SkinsType::downList());
            });

        });
        $grid->model()->orderByDesc('is_lucky_box')->orderByDesc('id');
        $grid->disableActions();
        $grid->column('id', 'Id');
        $grid->column('cover', '饰品封面')->lightbox(['width' => 75]);
        $grid->column('name', '饰品名称');
        $grid->column('dura', '饰品外观')->using(Skins::$fields['dura']);
        $grid->column('type', '类型')->editable('select',SkinsType::downList());
        $grid->column('luck_interval', '幸运区间')->editable();
        $grid->column('luck_interval_anchor', '幸运区间(主播)')->editable();
        $grid->column('剩余额度')->display(function () {
            $luk = Cache::get(Skins::$fields['cacheKey'][4].$this->id);
            if ($luk === null){
                return null;
            }
            return $luk / 100;
        })->help('还需要最低消费多少额度爆出该武器。');
        $grid->column('luk', '剩余额度(主播)')->display(function () {
            $luk = Cache::get(Skins::$fields['cacheKey'][5].$this->id);
            if ($luk === null){
                return null;
            }
            return $luk / 100;
        })->help('还需要最低消费多少额度爆出该武器。');
        $grid->column('is_lucky_box', '上架')->editable('select',[0=>'否',1=>'是'])->sortable();
        $grid->column('bean', getConfig('bean_name'))->sortable();
        $grid->column('lv', '品质')->using(Skins::$fields['lv']);

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Skins());
        $form->select('is_lucky_box', '上架')->options([0=>'否',1=>'是'])->required()->help('幸运开箱物品，选择是 装备将出现在首页幸运开箱列表。');
        $form->text('luck_interval', '幸运区间')->rules(['required','regex:/^[0-9]+\/[0-9]+$/'],[
            'required' => '区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 110/200'
        ])->help('区间开始结束值 如：110/200 110为开始值 200为结束值，结束值必须大于起始值。');
        $form->text('luck_interval_anchor', '幸运区间(主播)')->rules(['required','regex:/^[0-9]+\/[0-9]+$/'],[
            'required' => '主播区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 110/200'
        ])->help('主播幸运区间开始结束值 如：110/200 110为开始值 200为结束值，结束值必须大于起始值。');
        $form->select('type','饰品类型')->options(function () {
            return SkinsType::downList();
        })->required();

        return $form;
    }
}
