<?php

namespace App\Admin\Controllers;

use App\Skins;
use App\SkinsType;
use App\Admin\Actions\User\SendSkins;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Controllers\AdminController;

class SkinsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '饰品';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Skins());

        $script = <<<EOT
$.fn.modal.Constructor.prototype.enforceFocus = function () {};
$("div[id^='grid-modal-']").removeAttr('tabindex');
$("div[id='modal']").removeAttr('tabindex');
EOT;
        \Encore\Admin\Facades\Admin::script($script);

        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->column(1/4, function ($filter) {
            });
            $filter->column(1/4, function ($filter) {
                $filter->like('name', __('Name'));
            });
            $filter->column(1/4, function ($filter) {
                $filter->equal('type', '类型')->select(SkinsType::downList());
            });
            $filter->column(1/4, function ($filter) {
                $filter->equal('dura', '类型')->select(Skins::$fields['dura']);
            });
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->add(new SendSkins());
        });

        $grid->model()->orderByDesc('id');
        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->editable()->sortable();
        $grid->column('dura', __('Dura'))->using(Skins::$fields['dura']);
        $grid->column('rarity', '稀有度')->editable('select',Skins::$fields['rarity_cn'])->sortable();
        $grid->column('cover', __('Cover'))->lightbox(['width' => 75]);
        $grid->column('type',  __('Type'))->editable('select',SkinsType::downList());
        $grid->column('is_purse', '钱袋')->radio([0 => '否', 1 => '是'])->help('设置为是时提取和汰换无法使用');
        $grid->column('bean', getConfig('bean_name'))->editable()->sortable();
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
        $show = new Show(Skins::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('hash_name', 'Hash');
        $show->field('item_id', 'itemId');
        $show->field('cover', __('Cover'));
        $show->field('dura', __('Dura'))->using(Skins::$fields['dura']);
        $show->field('rarity', '品质')->using(Skins::$fields['rarity_cn']);
        $show->field('bean', getConfig('bean_name'));
        $show->field('type', __('Type'))->using(SkinsType::downList());
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
        $form = new Form(new Skins());
        
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        $form->text('name', __('Name'))->required();
        $form->text('hash_name', 'Hash')
            ->creationRules(['required', "unique:skins,hash_name"],[
                'required' => '请输入Hash！',
                'unique' => 'Hash已存在！'
            ])
            ->updateRules(['required', "unique:skins,hash_name,{{id}}"],[
                'required' => '请输入Hash！',
                'unique' => 'Hash已存在！'
            ])
            ->help('唯一英文名称，用于识别采集武器价格.');
        $form->text('item_id', 'itemId')
            ->creationRules(['required', 'integer' , "unique:skins,item_id"],[
                'required' => '请输入itemId！',
                'unique' => 'itemId已存在！'
            ])
            ->updateRules(['required', 'integer', "unique:skins,item_id,{{id}}"],[
                'required' => '请输入itemId！',
                'unique' => 'itemId已存在！'
            ])
            ->help('唯一itemId，用于发货使用.');
        $form->select('dura', __('Dura'))->options(Skins::$fields['dura'])->default(0)->required();
        $form->image('cover', __('Cover'))->uniqueName()->required()->move('images/skins');
        $form->radioCard('rarity', '品质')->options(Skins::$fields['rarity_cn'])->default(0)->required();
        $form->currency('bean', getConfig('bean_name'))->symbol('M')->default(1.00)->required();
        $form->radioButton('type', __('Type'))->options(SkinsType::downList())->default(1)->required();
        $form->radio('is_purse', '钱袋')->options([0 => '否', 1 => '是'])->default(0)->required()->help('设置为是时提取和汰换无法使用');
        //保存后执行
        $form->saved(function (Form $form) {
            \Cache::delete(Skins::$fields['cacheKey'][0]);
        });
        return $form;
    }
}
