<?php

namespace App\Admin\Controllers;

use App\Vip;
use App\User;
use App\BeanRecord;
use App\LoginIpLog;
use App\UserTagList;
use App\BaiduChannel;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Services\ZbtService;
use App\Admin\Actions\User\Log;
use Illuminate\Support\MessageBag;
use App\Admin\Actions\User\SetTag;
use App\Admin\Actions\User\Storage;
use App\Admin\Actions\User\Generate;
use App\Admin\Actions\User\BindSuperior;
use Encore\Admin\Controllers\AdminController;

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '用户';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        //禁用创建按钮
        $grid->disableCreateButton();
        $grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
                $filter->like('name', '昵称');
                $filter->equal('inviter_id', '上级ID');
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('mobile', '手机号')->mobile();
                $filter->like('invite_code', '邀请码');
                $filter->equal('tags.id', '标签')->select(UserTagList::all()->pluck('name','id'));
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('status', '状态')->select([0 => '封禁', 1 => '正常']);
                $filter->equal('anchor', '主播')->select([0 => '否', 1 => '是']);
                $filter->equal('baidu_channel_id', '渠道')->select(BaiduChannel::all()->pluck('name','id'));
            });
        });
        $grid->export(function ($export) {
            $export->except(['avatar', 'is_loss','tags']);
            $export->column('ban_pick_up', function ($value, $original) {
                if($original === 0){
                    return '否';
                }
                return '是';
            });
            $export->column('is_roll', function ($value, $original) {
                if($original === 0){
                    return '否';
                }
                return '是';
            });
            //is_ob_robot
            $export->column('anchor', function ($value, $original) {
                if($original === 0){
                    return '用户';
                }
                return '主播';
            });
            $export->column('is_ob_robot', function ($value, $original) {
                if($original === 0){
                    return '否';
                }
                return '是';
            });
            $export->column('status', function ($value, $original) {
                if($original === 0){
                    return '封禁';
                }
                return '正常';
            });
            $export->column('is_roll', function ($value, $original) {
                if($original === 0){
                    return '否';
                }
                return '是';
            });
            $export->column('登录Ip', function ($value, $original) {
                if (!empty($value)) {
                    return cut('>', '</', $value);
                }
                return null;
            });
        });
        //批量操作
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            $batch->add(new SetTag());
        });
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->add(new Storage());
            $actions->add(new Log());
            $actions->add(new BindSuperior());
            /*if ($actions->row->inviter_id === 0){
                $actions->add(new BindSuperior());
            }*/
        });
        //生产卡密
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Generate());
        });
        $grid->model()->orderBy('id', 'DESC');
        //$grid->total_recharge()->;
        $grid->column('id', 'Id');
        $grid->column('name', '用户名');
        $grid->column('avatar', '头像')->image('',50);
        $grid->column('mobile', '手机');
        $grid->column('vip_level', 'Vip')->using(Vip::$levelMap)->sortable();
        $grid->column('inviter.name', '上级');
        $grid->column('baiduChannel.name', '渠道');
        $grid->column('invite_code', '邀请码');
        $grid->column('直推人数')->display(function (){
            return User::where(['inviter_id' => $this->id])->count('id');
        });
        $grid->column('total_recharge', '总充值(真实)')->sortable();
        $grid->column('下级总充值')->display(function (){
            if ($this->anchor == 1){
                return BeanRecord::where(['inviter_id' => $this->id, 'status' => 1])->sum('bean');
            }
            return '非主播';
        });
        $grid->column('bean', getConfig('bean_name'))->sortable();
        $grid->column('integral', '积分')->sortable();
        $grid->column('loss', '亏损')->sortable()->help('用户亏损，正数表示亏损多少，负数表示赚了多少。');
        $states = [
            'on'  => ['value' => 1, 'text' => '打开', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
        ];
        //$grid->column('is_loss', '亏损回血')->radio([0=>'关闭',1=>'开启']);
        //$grid->column('close_gift', __('禁用赠送'))->switch($states);
        $grid->column('ban_pick_up', '禁用取货')->switch($states);
        $grid->column('is_roll', 'Roll 房号')->switch($states)->help('开启后无需验证充值条件即可进入Roll房');
        $grid->column('anchor', '主播')->radio([0 => '否', 1 => '是'])->help('选择为是开箱/对战/幸运饰品等走主播爆率');
        $grid->column('is_ob_robot', '开箱号')->radio([0 => '否', 1 => '是'])->help('选择为是开箱机器人将使用此账号进行开箱');
        $grid->column('status', '状态')->using([0 => '封禁', 1 => '正常'])->dot([
            0 => 'danger',
            1 => 'success'
        ], 'warning');
        //$grid->column('merchant', __('商人'))->radio([0 => '否', 1 => '是']);
        $grid->column('登录Ip')->display(function (){
            $ip = LoginIpLog::query()->where(['user_id' => $this->id])->orderByDesc('id')->first();
            if ($ip){
                //return $ip->ip;
                return '<a href="/'.config('admin.route.prefix').'/login-ip-logs?ip='.$ip->ip.'" target="_blank">'.$ip->ip.'</a>';
            }
            return null;
        });
        $grid->column('tags','标签')->display(function ($tags){
            $str = '';
            foreach ($tags as $key => $tag){
                if ($key > 0){
                    $str .= '&nbsp;<span class="label label-success">'.$tag['name'].'</span>';
                } else {
                    $str .= '<span class="label label-success">'.$tag['name'].'</span>';
                }
            }
            return $str;
        });
        $grid->column('created_at', '注册时间');
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
        $show = new Show(User::findOrFail($id));
        $show->field('id', 'Id');
        $show->field('avatar', '头像')->image();
        $show->field('name', '昵称');
        $show->field('mobile', '手机号');
        $show->field('email', '邮箱');
        $show->field('invite_code', '邀请码');
        $show->field('steam_url', 'Steam交易链接');
        $show->field('inviter.name', '邀请人');
        $show->field('bean', '余额');
        $show->field('integral', '积分');
        $show->field('promotion_level', '等级');
        $show->field('total_recharge', '总充值');
        $show->field('ban_pick_up', '禁止提货')->using([0 => '正常', 1 => '禁用']);
        $show->field('status', '账号状态')->using([0 => '封禁', 1 => '正常']);
        $show->field('created_at', '注册时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        $form->text('name', '昵称');
        $form->image('avatar', '头像')->resize(125,125)->uniqueName();
        $form->mobile('mobile', '手机号')->disable();
        $form->email('email', '邮箱')->disable();
        $form->text('invite_code', '邀请码')->rules(['required','min:5', 'unique:users,invite_code,{{id}}'],[
            'required' => '推广码不能为空',
            'min' => '推广码不能小于五个字符',
            'unique' => '推广码被占用',
        ]);
        $form->text('steam_url', 'Steam交易链接');
        $form->password('password', '密码');
        $form->currency('bean', getConfig('bean_name'))->symbol('M');
        $states = [
            'on'  => ['value' => 1, 'text' => '打开', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
        ];
        //$form->switch('close_gift',__('禁用赠送'))->states($states)->help('开启后用户装备无法赠送他人');
        $form->switch('ban_pick_up','禁用取货')->states($states)->help('开启后用户装备无法申请提货');
        $form->radio('anchor','主播')->options([0 => '否', 1 => '是'])->help('选择为是开箱/对战/幸运饰品等走主播爆率');
        $form->radio('is_ob_robot','开箱号')->options([0 => '否', 1 => '是'])->help('选择为是开箱机器人将使用此账号进行开箱');
        $form->switch('is_roll','Roll房号')->states($states)->help('开启后无需验证充值条件即可进入Roll房');
        /*$form->radio('is_loss','亏损回血')->options([0 => '关闭', 1 => '开启'])->default(0)->help('打开战损回血开关后，玩家开箱将按照亏损值 * 0.6去查找宝箱内有没有符合回血的饰品，如果有则爆出，如果没有则回血不被触发，爆出物品不在宝箱奖池爆率范围内，累计触发10次回血或亏损为0时自动关闭回血开关。');*/
        $form->multipleSelect('tags', '标签')->options(UserTagList::all()->pluck('name', 'id'));
        $form->radioButton('status','状态')->options([0 => '封禁', 1 => '正常'])->help('账号状态，封禁时账号将无法登录.');
        $form->hidden('steam_id');
        //$form->radio('merchant',__('商人'))->options([0 => '否', 1 => '是'])->help('设置用户为商人');
        $form->saving(function (Form $form) {
            if ($form->password && !password_verify($form->password,$form->model()->password)) {
                $form->password = password_hash($form->password, PASSWORD_DEFAULT);
            }else{
                $form->password = $form->model()->password;
            }
            try{
                if (!empty($form->steam_url) && $form->model()->steam_url !== $form->steam_url){
                    //检测账号状态
                    $zbt_check = ZbtService::steam_check($form->steam_url);
                    if ($zbt_check['code'] == 0){
                        throw new \Exception($zbt_check['message'], -1);
                    }
                    //获得Steam_Id
                    $zbt_res = ZbtService::steam_info($form->steam_url);
                    if ($zbt_res['code'] == 0) {
                        throw new \Exception($zbt_res['message'], -1);
                    }
                    $steamId = $zbt_res['data']['steamInfo']['steamId'];
                    $is_steam_url = User::where('steam_id', $steamId)
                        ->where('id','!=',$form->model()->id)
                        ->exists();
                    //检测占用
                    if ($is_steam_url){
                        throw new \Exception('Steam 账号已被其他其他用户绑定', -1);
                    }
                    $form->steam_id = $steamId;
                } elseif (!empty($form->steam_url) && $form->model()->steam_url == $form->steam_url) {
                    $form->steam_id = $form->model()->steam_id;
                    $form->steam_url = $form->model()->steam_url;
                } elseif (empty($form->steam_url)) {
                    $form->steam_url = '';
                    $form->steam_id = null;
                }
            } catch (\Exception $e){
                $error = new MessageBag([
                    'title'   => '错误',
                    'message' => $e->getMessage(),
                ]);
                return back()->with(compact('error'));
            }

        });
        return $form;
    }
}
