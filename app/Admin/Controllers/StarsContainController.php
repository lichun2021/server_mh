<?php

namespace App\Admin\Controllers;

use App\Skins;
use App\SkinsLv;
use App\StarsContain;
use App\StarsList;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Support\Facades\Redis;

class StarsContainController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '红星轮盘包含饰品';

    private static $views = [
        0 => '否',
        1 => '是'
    ];

    private static $switch = [
        'on' => ['value' => 1, 'text' => '是', 'color' => 'success'],
        'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StarsContain());
        //禁用创建按钮
        $grid->disableCreateButton();
        //筛选
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('skins.name', '饰品名称');
        });
        //禁用操作
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        //查询条件
        $grid->model()
            ->select(['stars_contains.*', 'skins.id as skins_id', 'skins.bean'])
            ->Join('skins', 'skins.id', '=', 'stars_contains.skin_id')
            ->where('stars_id', request()->get('stars_id'))
            ->orderByDesc('skins.bean');
        //
        $grid->header(function () {
            return '<br>使用说明：L1-L6是每件饰品在1-6开中的等级，显示配置是每件饰品1-6开中的显示开关，请确保每一开中显示8件饰品！U1-U6用户爆率，A1-A6主播爆率，新版调整任意参数1-6开奖池都会清空，参数即时生效。</br>';
        });

        $grid->column('id', 'Id');
        $grid->column('skins.cover', '饰品图')->lightbox(['width' => 50]);
        $grid->column('skins.name', '饰品名称')->display(function () {
            if ($this->skins->dura === 0) {
                return $this->skins->name;
            }
            return $this->skins->name . ' (' . Skins::$fields['dura'][$this->skins->dura] . ')';
        });
        $grid->column('stars.name', '所属星星');
        $grid->column('bean', getConfig('bean_name'));
        $grid->column('l1', 'L1')->editable('select', SkinsLv::downList());
        $grid->column('l2', 'L2')->editable('select', SkinsLv::downList());
        $grid->column('l3', 'L3')->editable('select', SkinsLv::downList());
        $grid->column('l4', 'L4')->editable('select', SkinsLv::downList());
        $grid->column('l5', 'L5')->editable('select', SkinsLv::downList());
        $grid->column('l6', 'L6')->editable('select', SkinsLv::downList());
        $grid->column('v1', '显示配置')->switchGroup([
            'v1' => '① 开显示',
            'v2' => '② 开显示',
            'v3' => '③ 开显示',
            'v4' => '④ 开显示',
            'v5' => '⑤ 开显示',
            'v6' => '⑥ 开显示',
        ], self::$switch);
        $grid->column('u1', 'U1')->editable();
        $grid->column('u2', 'U2')->editable();
        $grid->column('u3', 'U3')->editable();
        $grid->column('u4', 'U4')->editable();
        $grid->column('u5', 'U5')->editable();
        $grid->column('u6', 'U6')->editable();
        $grid->column('a1', 'A1')->editable();
        $grid->column('a2', 'A2')->editable();
        $grid->column('a3', 'A3')->editable();
        $grid->column('a4', 'A4')->editable();
        $grid->column('a5', 'A5')->editable();
        $grid->column('a6', 'A6')->editable();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StarsContain());

        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`查看`按钮
            $tools->disableView();
        });

        $form->divider('饰品配置');
        $form->select('skin_id', '饰品')->options(function ($id) {
            $skins = Skins::find($id);
            if ($skins) {
                return [$skins->id => $skins->name . ' (' . Skins::$fields['dura'][$skins->dura] . ')'];
            }
        })->ajax('/' . config('admin.route.prefix') . '/api/skins')->required();
        $form->select('l1', '① 开等级')->options(SkinsLv::downList());
        $form->select('l2', '② 开等级')->options(SkinsLv::downList());
        $form->select('l3', '③ 开等级')->options(SkinsLv::downList());
        $form->select('l4', '④ 开等级')->options(SkinsLv::downList());
        $form->select('l5', '⑤ 开等级')->options(SkinsLv::downList());
        $form->select('l6', '⑥ 开等级')->options(SkinsLv::downList());
        $form->divider('显示配置');
        $form->switch('v1', '① 开显示')->states(self::$switch);
        $form->switch('v2', '② 开显示')->states(self::$switch);
        $form->switch('v3', '③ 开显示')->states(self::$switch);
        $form->switch('v4', '④ 开显示')->states(self::$switch);
        $form->switch('v5', '⑤ 开显示')->states(self::$switch);
        $form->switch('v6', '⑥ 开显示')->states(self::$switch);

        $form->divider('爆率配置');
        $form->number('u1', '用户1')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('a1', '主播1')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('u2', '用户2')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('a2', '主播2')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('u3', '用户3')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('a3', '主播3')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('u4', '用户4')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('a4', '主播4')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('u5', '用户5')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('a5', '主播5')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('u6', '用户6')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);
        $form->number('a6', '主播6')->default(0)->rules('integer|min:0|max:255', [
            'integer' => '请输入整数',
            'min' => '请输入0-255的数字',
            'max' => '请输入0-255的数字',
        ]);

        $form->saved(function (Form $form) {
            if ($form->model()->stars_id) {
                for ($i = 0; $i < 6; $i++) {
                    $num = $i + 1;
                    $userKey = StarsList::$fields['cacheKey'][1] . 'user_' . $form->model()->stars_id . '_' . $num;
                    $anchorKey = StarsList::$fields['cacheKey'][1] . 'anchor_' . $form->model()->stars_id . '_' . $num;
                    Redis::del($userKey);
                    Redis::del($anchorKey);
                }
            }

        });
        return $form;
    }
}
