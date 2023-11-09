<?php

namespace App\Admin\Controllers;

use App\Vip;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;

/**
 * Class VipController
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/6/9
 * Time：22:58
 */
class VipController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vip等级';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Vip());
        $grid->disableCreateButton();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用筛选
        $grid->disableFilter();
        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableView();
        });

        $grid->column('level', '等级')->using(Vip::$levelMap);
        $grid->column('threshold','累充门槛');
        $grid->column('rebate', '充返比率(%)');
        $grid->column('packet', '奖励红包');
        $grid->column('description', '描述');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Vip());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->select('level', '等级')->options(Vip::$levelMap)->rules(['required'],[
            'required' => '请选择VIP等级',
        ]);
        $form->decimal('threshold', '累充门槛')->rules(['required','numeric','min:0'],[
            'required' => '请输入累充门槛',
            'numeric' => '累充门槛输入有误',
            'min' => '累充门槛不能是负数',
        ])->default(0);
        $form->decimal('rebate', '充返比率(%)')->rules(['required','numeric','min:0'],[
            'required' => '请输入充返比率',
            'numeric' => '充返比率输入有误',
            'min' => '充返比率不能是负数',
        ])->default(0);
        $form->decimal('packet', '奖励红包')->rules(['required','numeric','min:0'],[
            'required' => '请输入奖励红包',
            'numeric' => '奖励红包输入有误',
            'min' => '奖励红包不能是负数'
        ])->default(0);
        $form->textarea('description','描述')->rules(['string','max:255'],[
            'string' => '描述输入有误',
            'max' => '描述最多支持255个字符',
        ]);
        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        return $form;
    }
}
