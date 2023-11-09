<?php

namespace App\Admin\Controllers;

use App\User;
use App\Room;
use App\Skins;
use App\BoxRecord;
use App\RoomAward;
use App\RoomJackpot;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\RoomJackpotsList;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;
use App\Admin\Actions\Room\BatchIn;
use App\Admin\Actions\Room\UserList;
use Encore\Admin\Controllers\AdminController;

class RoomController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Roll房';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Room());

        $grid->actions(function ($actions) {
            $actions->add(new UserList());
            if ($actions->row['status'] === 0) {
                $actions->add(new BatchIn());
            }
        });

        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->column(1 / 3, function ($filter) {
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->like('name', '房间名');
            });
            $filter->column(1 / 3, function ($filter) {
                $filter->equal('status', '状态')->select(Room::$fields['status']);
            });

        });

        $grid->model()->orderBy('status')->orderByDesc('top')->orderBy('type')->orderByDesc('id');
        $grid->column('id', 'Id');
        $grid->column('name', '房间名');
        $grid->column('type', '房间类型')->using(Room::$fields['type'])->label([
            0 => 'info',
            1 => 'danger'
        ]);
        $grid->column('user.name', '创建用户');
        $grid->column('password', __('密码'));
        $grid->column('people_number', '可参与人数');
        $grid->column('end_time', '开奖时间');
        $grid->column('pay_start_time', '计算充值起始日期')->editable('datetime');
        $grid->column('min_recharge', '充值' . getConfig('bean_name'));
        $grid->column('status', __('状态'))->using(Room::$fields['status'])->dot([
            0 => 'success',
            1 => 'danger',
        ], 'warning');
        $states = [
            'on' => ['value' => 1, 'text' => '打开', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
        ];
        $grid->column('top', __('置顶'))->switch($states);
        $grid->column('created_at', '创建时间');

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
        $show = new Show(Room::findOrFail($id));

        $show->field('id', 'Id');
        $show->field('name', '房间名');
        $show->field('type', '房间类型')->using(Room::$fields['type']);
        $show->field('user.name', '创建用户');
        $show->field('describe', '房间描述');
        $show->field('people_number', '可参与人数');
        $show->field('password', '密码');
        $show->field('end_time', '开奖时间');
        $show->field('pay_start_time', '计算充值起始日期');
        $show->field('min_recharge', '充值' . getConfig('bean_name'));
        $show->field('status', '状态')->using(Room::$fields['status']);
        $show->field('top', '状态')->using([0 => '否', 1 => '是']);
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '修改时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Room());
        $states = [
            'on' => ['value' => 1, 'text' => '打开', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
        ];
        if ($form->isCreating()) {
            $form->select('type', '房间类型')->options(Room::$fields['type'])->rules('required', [
                'required' => '请选择房间类型'
            ])->when(1, function (Form $form) use ($states) {
                $form->select('user_id', '主播')->options(
                    User::query()->pluck('name', 'id')->toArray()
                );
                $form->switch('me_inviter', '只许主播下级参与')->states($states);
            });
        }
        $form->text('name', '房间名')->rules('required|min:4|max:20', [
            'required' => '请输入房间名',
            'min' => '房间名不能小于4个字符',
            'max' => '房间名最多支持20个字符',
        ]);
        $form->textarea('describe', '描述')->rules('max:255', [
            'max' => '房间描述最多支持255个字符'
        ]);
        if ($form->isCreating()) {
            $form->select('skins', '奖池')->options(RoomJackpot::pluck('name', 'id')->toArray())->rules('required', [
                'required' => '请选择Roll房奖池'
            ]);
        }
        $form->datetime('end_time', '开奖时间')->default(date('Y-m-d H:i:s'))->rules('required', [
            'required' => '请选择开奖时间'
        ]);
        $form->number('people_number', '可参与人数')->rules('required|integer|min:1|max:65535', [
            'required' => '请输入可参与人数',
            'integer' => '可参与人数错误',
            'min' => '可参与人数不能小于1',
            'max' => '可参与人数最大支持65535',
        ])->default(100);
        $form->text('password', '房间密码')->rules('max:16', [
            'max' => '房间密码最多支持16个字符'
        ])->help('为空表示无密码');
        $form->datetime('pay_start_time', '计算充值起始日期')->default(date('Y-m') . '-1 00:00:00')->help('计算充值起始时间');
        $form->decimal('min_recharge', '充值' . getConfig('bean_name'))->rules('required|integer|min:0', [
            'required' => '充值金额不能为空',
            'integer' => '充值金额错误',
            'min' => '充值金额最小值为0',
        ])->default(0.00)->help('为0时表示不做限制');
        $form->switch('top', __('置顶'))->states($states);
        $form->radioButton('status', '状态')->options([
            0 => '立即上架',
            -1 => '定时上架',
        ])->when(-1, function (Form $form) {
            $form->datetime('start_at', '上架时间')->default(date('Y-m-d H:i:s'));
        });
        $form->saving(function (Form $form) {
            if ($form->type == 0) {
                $user = User::find(getConfig('official_user_id'));
                if (!$user) {
                    $error = new MessageBag([
                        'title' => '配置错误',
                        'message' => '未设置官方账号Id或官方账号Id设置有误！',
                    ]);
                    return back()->with(compact('error'));
                }
                $form->user_id = getConfig('official_user_id');
            }
        });
        //保存后回调
        if ($form->isCreating()) {
            $form->saved(function (Form $form) {

                DB::beginTransaction();
                try {
                    $jackpot = RoomJackpot::find($form->model()->skins);
                    if (!$jackpot) {
                        throw new \Exception('你选择的奖池不存在，请检查奖池是否被删除！');
                    }
                    $list = RoomJackpotsList::where(['jackpot_id' => $form->model()->skins])->get()->toArray();
                    if (empty($list)) {
                        throw new \Exception('你选择的奖池内未分配饰品，请先分配饰品！');
                    }
                    foreach ($list as $item) {
                        for ($i = 0; $i < $item['num']; $i++) {
                            $skins = Skins::find($item['skin_id']);
                            if (!$skins) {
                                throw new \Exception('奖池中的饰品未在饰品表中找到，饰品数据被删除！');
                            }
                            $box_record = new BoxRecord();
                            $box_record->get_user_id = $form->model()->user_id;
                            $box_record->user_id = $form->model()->user_id;
                            //$box_record->box_id = $box->id;
                            $box_record->box_name = '创建Roll房';
                            $box_record->skin_id = $item['skin_id'];
                            $box_record->name = $skins->name;
                            $box_record->cover = $skins->getRawOriginal('cover');
                            $box_record->dura = $skins->dura;
                            $box_record->bean = $skins->bean;
                            $box_record->code = getUniqueOrderNumber();
                            $box_record->status = 3;
                            $box_record->type = 0;
                            $box_record->is_purse = $skins->is_purse;
                            if ($box_record->save()) {
                                $room_award = new RoomAward();
                                $room_award->room_id = $form->model()->id;
                                $room_award->box_record_id = $box_record->id;
                                if (!$room_award->save()) {
                                    throw new \Exception('福利房奖品入库保存失败！');
                                }
                            } else {
                                throw new \Exception('饰品入库保存失败！');
                            }
                        }
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();
                    $form->model()->delete();
                    $error = new MessageBag([
                        'title' => '错误',
                        'message' => $e->getMessage(),
                    ]);
                    return back()->with(compact('error'));
                }
            });
        }
        return $form;
    }
}
