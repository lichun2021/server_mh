<?php

namespace App\Admin\Controllers;

use App\BoxAward;
use App\Skins;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Support\Facades\Cache;

class SynthesisController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '装备合成';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Skins());
        //禁用创建按钮
        $grid->disableCreateButton();

        $grid->filter(function($filter){
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/2, function ($filter) {
                $filter->like('name', '饰品名称');
            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('is_synthesis', '上架')->select([0 => '否',1 => '是']);
            });
        });
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行操作列
        $grid->disableActions();
        //排序
        $grid->model()->orderBy('is_synthesis', 'DESC')->orderBy('id');

        $grid->column('id', 'Id');
        $grid->column('name', '名称');
        $grid->column('dura', '外观')->using(Skins::$fields['dura']);
        $grid->column('cover', '封面')->lightbox(['width' => 75]);
        $grid->column('bean', getConfig('bean_name'));
        $grid->column('合成进度')->display(function () {
            $luk = Cache::get('synthesis_cache_'.$this->id);
            $bean = bcmul($this->bean,1.1,2) * 100;
            $multiple = bcdiv($bean,100,2);
            return bcdiv($luk ,$multiple,2);
        })->progressBar($style = 'primary', $size = 'sm', $max = 100)->help('装备合成进度 100% 合成成功！');
        $grid->column('luk', '合成进度(主播)')->display(function () {
            $luk = Cache::get('synthesis_cache_anchor_'.$this->id);
            $bean = bcmul($this->bean,1.1,2)  * 100;
            $multiple = bcdiv($bean,100,2);
            return bcdiv($luk ,$multiple,2);
        })->progressBar($style = 'primary', $size = 'sm', $max = 100)->help('装备合成进度 100% 合成成功！');
        $grid->column('is_synthesis','上架')->editable('select',[0 => '否',1 => '是'])->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Skins());

        $form->switch('is_synthesis', __('Is synthesis'));

        return $form;
    }
}
