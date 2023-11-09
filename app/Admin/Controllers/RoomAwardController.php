<?php

namespace App\Admin\Controllers;

use App\RoomAward;
use App\Admin\Actions\Room\SetUser;
use App\RoomUser;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RoomAwardController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '房间奖项';


    protected $model_id;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $script = <<<EOT
$.fn.modal.Constructor.prototype.enforceFocus = function () {};
$("div[id^='grid-modal-']").removeAttr('tabindex');
$("div[id='modal']").removeAttr('tabindex');
EOT;
        \Encore\Admin\Facades\Admin::script($script);

        $grid = new Grid(new RoomAward());
        $grid->disableCreateButton();

        //禁用查询过滤器
        $grid->disableFilter();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();

        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            if ($actions->row['get_user_id'] == 0) {
                $actions->add(new SetUser());
            }
        });
        //$grid->model()->where('room_id',request()->get('room_id'));
        $grid->model()
            ->select(['room_awards.*','box_records.id as boxRecordsId','box_records.bean'])
            ->leftJoin('box_records','box_records.id','=','room_awards.box_record_id')
            ->where('room_id',request()->get('room_id'))
            ->orderByDesc('box_records.bean');
        $grid->column('id', 'Id');
        $grid->column('box_record.name', '装备名称');
        $grid->column('box_record.cover', '装备外观')->image('',75);
        $grid->column('box_record.bean', getConfig('bean_name'));
        $grid->column('获奖用户')->display(function () {
            if ($this->get_user_id == 0){
                return '无';
            }
            return $this->user->name ?? null;
        });
        $grid->column('指定用户')->display(function () {
            if ($this->designated_user == 0){
                return '<span class="label label-success" title="未指定">无</span>';
            }
            $nickname = $this->designated->name ?? null;
            return '<span class="label label-danger" title="对应R币充值列表已被删除，首冲关系失效！">'.$nickname.'</span>';
        });
        $grid->column('room.end_time', '开奖时间');

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
        $show = new Show(RoomAward::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('room_id', __('Room id'));
        $show->field('box_record_id', __('Box record id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('deleted_at', __('Deleted at'));
        $show->field('get_user_id', __('Get user id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new RoomAward());

        $form->select('designated', '指定获奖者')->options(RoomUser::roomUserList(request()->get('room_id')));
        return $form;
    }
}
