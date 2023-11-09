<?php

namespace App\Admin\Controllers;

use App\Skins;
use App\Snatch;
use App\SnatchAward;
use App\Admin\Actions\Snatch\WinUser;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SnatchController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '幸运夺宝房';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Snatch());

        //禁用查询过滤器
        $grid->disableFilter();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableView();
            if ($actions->row['status'] == 0) {
                $actions->add(new WinUser());
            }
        });
        $grid->model()->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('name', '名称');
        $grid->column('brief', '简述');
        $grid->column('user_max_num', '开奖份数');
        $grid->column('total_bean', '饰品价值');
        $grid->column('expend_bean', '参与费用(每/份)');
        $grid->column('win_user.name','指定获胜用户');
        $grid->column('status', '状态')->using([ 0 => '等待中', 1 => '已开奖'])->dot([
            0 => 'success',
            1 => 'danger',
        ]);
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('开奖时间'))->display(function () {
            $time = $this->updated_at;
            return $this->status == 0 ? '' : $time->format('Y-m-d H:i:s');
        });

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
        $show = new Show(Snatch::findOrFail($id));

        $show->field('id', 'Id');
        $show->field('name', '名称');
        $show->field('brief', '简述');
        $show->field('user_max_num', '开奖份数');
        $show->field('total_bean', '饰品价值');
        $show->field('expend_bean', '参与费用(每/份)');
        $show->field('status', '状态');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '开奖时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Snatch());

        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            //$tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();

            //$tools->append("<a class='btn btn-sm btn-primary mallto-next'>复制装备名称</a> &nbsp;");

        });
        $form->footer(function ($footer) {
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });

        $form->text('name', '名称')->required();
        $form->textarea('brief', '简述');
        $form->select('award_id', '饰品')->ajax('/'.config('admin.route.prefix').'/api/snatch-skins')->required();
        $form->currency('expend_bean','参与费用')->symbol('M')->required();
        $form->number('user_max_num', '开奖份数')->required();
        $form->hidden('total_bean');
        //保存前回调
        $form->saving(function (Form $form) {
            $skins = Skins::where('id', $form->award_id)->first();
            $form->total_bean = $skins->bean;
        });

        $form->saved(function (Form $form) {
            $snatchAward = new SnatchAward();
            $snatchAward->snatch_id = $form->model()->id;
            $snatchAward->box_award_id = $form->award_id;
            $snatchAward->save();
        });
        return $form;
    }
}

?>
