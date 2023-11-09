<?php

namespace App\Admin\Controllers;

use App\Vip;
use App\Room;
use App\Skins;
use App\RoomAward;
use App\RoomUser;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Actions\Room\AssignAward;
use Encore\Admin\Controllers\AdminController;

class RoomUserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '房间用户';

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

        $grid = new Grid(new RoomUser());
        $grid->disableCreateButton();

        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.mobile', '用户手机');
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.name', '用户名');
            });
        });

        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            $room = Room::find($actions->row['room_id']);
            if ($room->status === 0) {
                $actions->add(new AssignAward());
            }

        });

        $grid->model()->where('room_id', request()->get('room_id'));
        $grid->column('id', 'Id');
        $grid->column('user.avatar', '用户头像')->image('', 50);
        $grid->column('user.name', '用户名');
        $grid->column('user.vip_level', 'Vip等级')->using(Vip::$levelMap);
        $grid->column('user.anchor', '用户类型')->using([
            0 => '用户',
            1 => '主播'
        ])->label([
            0 => 'success',
            1 => 'danger',
        ]);
        $grid->column('user.total_recharge', '总充值');
        $grid->column('user.bean', getConfig('bean_name') . '余额');
        $grid->column('user.loss', '亏损')->help('用户亏损，正数表示亏损多少，负数表示赚了多少。');;
        $grid->column('user.mobile', '系统号')->display(function ($mobile) {
            $str = substr($mobile, 0, 2);
            if ($str === '12') {
                return '<i class="fa fa-check text-green"></i>';
            }
            return '<i class="fa fa-close text-red"></i>';
        })->help('通过后台生成的账号会显示为对号');
        $grid->column('指定饰品')->display(function (){
            $roomAward = RoomAward::where(['room_id' => $this->room_id, 'designated_user' => $this->user_id])->first();
            if ($roomAward){
                $skin = $roomAward->box_record;
                if ($skin->dura > 0){
                    return $roomAward->box_record->name.' (' . Skins::$fields['dura'][$skin->dura] . ')';
                }
                return $roomAward->box_record->name;
            }
            return null;
        });
        $grid->column('饰品价格')->display(function (){
            $roomAward = RoomAward::where(['room_id' => $this->room_id, 'designated_user' => $this->user_id])->first();
            if ($roomAward){
                $skin = $roomAward->box_record;
                return $skin->bean;
            }
            return null;
        });

        return $grid;
    }
}
