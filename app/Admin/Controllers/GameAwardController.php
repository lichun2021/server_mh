<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/11/17 16:25
 */

namespace App\Admin\Controllers;

use App\Box;
use App\GameArenaBox;
use App\Skins;
use App\BoxContain;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\MessageBag;

class GameAwardController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '对战宝箱奖项';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BoxContain());

        //禁用创建按钮
        $grid->disableCreateButton();
        $grid->tools(function ($tools) {
            $tools->append("<a href='".'/'.config('admin.route.prefix').'/game-awards/create?box_id=' . request()->get('box_id')."' class='btn btn-sm btn-success' style='float: right;'><i class='fa fa-plus'></i>&nbsp;&nbsp;新增</a>");
        });
        //禁用查询过滤器
        //$grid->disableFilter();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用列选择器
        $grid->disableRowSelector();
        //禁用行选择器
        $grid->disableColumnSelector();
        $grid->actions(function ($actions) {
            // 去掉查看
            //$actions->disableEdit();
            $actions->disableView();
        });
        $grid->model()->select(['box_contains.*','skins.id as skins_id','skins.bean'])
            ->Join('skins','skins.id','=','box_contains.skin_id')
            ->where('box_id', request()->get('box_id', 0))
            ->orderByDesc('skins.bean');
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];
        $grid->column('id', __('Id'));
        $grid->column('skins.name', __('Name'));
        $grid->column('skins.dura', __('Dura'))->using(Skins::$fields['dura']);
        $grid->column('skins.cover', '封面')->image('', 75);
        $grid->column('odds', __('Odds'))->editable()->totalRow();
        $grid->column('game_odds','对战爆率')->editable();
        $grid->column('game_anchor_odds', '对战爆率（主播）')->editable();
        $grid->column('skins.bean', getConfig('bean_name'));
        $grid->column('is_game', '上架')->switch($states);
        $grid->column('created_at', __('Created at'));
        $grid->header(function ($query) {
            $contains = $query->where('box_contains.is_game', 1)->get()->toArray();
            $user_bean = 0;
            $anchor_bean = 0;
            $box_id = request()->get('box_id');
            $box = false;
            if ($box_id){
                $box = Box::find($box_id);
            }
            foreach ($contains as $contain){
                if ($contain['game_odds'] > 0){
                    $user_bean += $contain['game_odds'] * $contain['bean'];
                }
                if ($contain['game_anchor_odds'] > 0){
                    $anchor_bean += $contain['game_anchor_odds'] * $contain['bean'];
                }
            }

            $box_user_bean = 0;
            $box_anchor_bean = 0;
            if ($box){
                $box_user_bean = bcmul($query->sum('game_odds'),$box->game_bean,2);
                $box_anchor_bean = bcmul($query->sum('game_anchor_odds'),$box->game_bean,2);
            }
            $boxName = $box->name ?? '';
            return  '<b>宝箱名称：'.$boxName.'<br>用户：奖池<b>'.$query->sum('game_odds').'</b>件，奖池总价 <b>'.$user_bean.'</b>，每轮花费<b>'.$box_user_bean.'</b>，每轮盈利：<b>'.bcsub($box_user_bean,$user_bean,2).'</b><br>主播：奖池<b>'.$query->sum('game_anchor_odds').'</b>件，奖池总价 <b>'.$anchor_bean.'</b>，每轮花费<b>'.$box_anchor_bean.'</b>，每轮盈利：<b>'.bcsub($box_anchor_bean,$anchor_bean,2).'</b>';
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BoxContain());
        $form->tools(function (Form\Tools $tools){
            // 去掉`列表`按钮
            $tools->disableList();
            $tools->disableView();
            $box_id = request()->get('box_id');
            if (empty($box_id) && request()->route()->parameter('game_award')){
                $contain = BoxContain::find(request()->route()->parameter('game_award'));
                $box_id = $contain->box_id;
            }
            $tools->add('<a href="'.'/'.config('admin.route.prefix').'/game-awards?box_id=' . $box_id.'" class="btn btn-sm btn-default" title="列表"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>');
        });
        $states = [
            'on' => ['value' => 1, 'text' => '是', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
        ];

        if ($form->isCreating()){
            $form->select('box_id', '宝箱')->options(Box::getList())->default(function (){
                return request()->get('box_id',null);
            })->required();
        }
        $form->select('skin_id', '饰品')->required()->options(function ($id) {
            $skins = Skins::find($id);
            if ($skins) {
                return [$skins->id => $skins->name.' ('. Skins::$fields['dura'][$skins->dura] .')'];
            }
        })->ajax('/'.config('admin.route.prefix').'/api/skins');
        $form->number('odds', __('Odds'))->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:10000',
        ], [
            'required' => '请输入显示爆率',
            'integer' => '请输入整数',
            'min' => '数值不能为负数',
            'max' => '数值超出限制最大支持10000',
        ]);
        $form->number('game_odds', '对战爆率')->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:10000',
        ], [
            'required' => '请输入显示爆率',
            'integer' => '请输入整数',
            'min' => '数值不能为负数',
            'max' => '数值超出限制最大支持10000',
        ]);
        $form->number('game_anchor_odds', '对战爆率（主播）')->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:10000',
        ], [
            'required' => '请输入主播爆率',
            'integer' => '请输入整数',
            'min' => '数值不能为负数',
            'max' => '数值超出限制最大支持10000',
        ]);
        $form->switch('is_game', '上架')->states($states);
        //保存前回调
        $form->saving(function (Form $form) {
            $model = $form->model();
            $isUse = BoxContain::where(['box_id' => $form->box_id,'skin_id' => $form->skin_id])->first();
            if ($isUse && $model->id != $isUse->id){
                $error = new MessageBag([
                    'title'   => '奖项重复',
                    'message' => '选择的饰品已在该宝箱中存在，单宝箱不允许饰品重复！',
                ]);
                return back()->with(compact('error'));
            }
        });
        //保存后回调
        $form->saved(function (Form $form) {
            //Lock Key
            $lockKey = GameArenaBox::$fields[0] . $form->model()->box_id;
            $lock_anchor_Key = GameArenaBox::$fields[1] . $form->model()->box_id;
            //Cache Key
            $key = GameArenaBox::$fields[2] . $form->model()->box_id;
            $anchor_key = GameArenaBox::$fields[3] . $form->model()->box_id;
            //拿到Cache原子锁 最多锁十秒
            $cacheLock = Cache::lock($lockKey, 10);
            $cacheLock_anchor = Cache::lock($lock_anchor_Key, 10);
            try {
                $cacheLock->block(10);
                $cacheLock_anchor->block(10);
                Redis::del($key);
                Redis::del($anchor_key);
                $cacheLock->release();
                $cacheLock_anchor->release();
                //保存后跳转
                $editable = request()->post('_editable');
                $previous = request()->post('_previous_');
                $box_id = request()->get('box_id',null);
                if ($form->model()->box_id){
                    $box_id = $form->model()->box_id;
                }
                //释放锁
                if ($editable === null && $previous != null){
                    return redirect('/'.config('admin.route.prefix').'/game-awards?box_id=' . $box_id);
                }
            } catch (\Exception $e) {
                //释放锁
                $cacheLock->release();
                $cacheLock_anchor->release();
                throw new \Exception("无法拿到缓存原子锁！", -1);
            }
        });
        return $form;
    }
}
