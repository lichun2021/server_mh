<?php

namespace App\Admin\Controllers;

use App\Box;
use App\BoxContain;
use App\Skins;
use App\Welfare;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Redis;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BoxContainController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '宝箱包含饰品';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BoxContain());
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            //$filter->disableIdFilter();
            $filter->like('skins.name', __('Name'));
        });
        $grid->disableCreateButton();
        $grid->tools(function ($tools) {
            $tools->append("<a href='".'/'.config('admin.route.prefix').'/box-contains/create?box_id=' . request()->get('box_id')."' class='btn btn-sm btn-success' style='float: right;'><i class='fa fa-plus'></i>&nbsp;&nbsp;新增</a>");
        });
        $grid->export(function ($export) {
            $export->column('skins.cover', function ($value, $original) {
                return $original;

            });
            $export->column('level', function ($value, $original){
                return BoxContain::$fields['lv'][$original];
            });
            /*$export->column('odds', function ($value, $original) {
                return $original;

            });*/
            $export->column('real_odds', function ($value, $original) {
                return $original;

            });
            /*$export->column('anchor_odds', function ($value, $original) {
                return $original;

            });*/
            $export->column('is_luck', function ($value, $original){
                return BoxContain::$fields['is_luck'][$original];
            });
            $export->column('luck_odds', function ($value, $original) {
                return $original;

            });
        });
        $grid->model()
            ->select(['box_contains.*','skins.id as skins_id','skins.bean'])
            ->Join('skins','skins.id','=','box_contains.skin_id')
            ->where('box_id',request()->get('box_id'))
            ->orderByDesc('skins.bean');
        $grid->column('id', __('Id'));
        $grid->column('skins.cover', '饰品图')->lightbox(['width' => 50]);
        $grid->column('skins.name', '饰品名称')->display(function (){
            if ($this->skins->dura === 0){
                return $this->skins->name;
            }
            return $this->skins->name .' ('. Skins::$fields['dura'][$this->skins->dura] .')';
        });
        $grid->column('level', '颜色')->editable('select',BoxContain::$fields['lv']);
        $grid->column('odds', __('Odds'))->editable()->totalRow();
        $grid->column('real_odds', __('Real odds'))->editable();
        $grid->column('anchor_odds', __('Anchor odds'))->editable();
        $grid->column('is_luck', __('Is luck'))->radio(BoxContain::$fields['is_luck']);
        $grid->column('luck_odds', __('Luck odds'))->editable();
        $grid->column('bean', getConfig('bean_name'))->sortable();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->header(function ($query) {
            $contains = $query->get()->toArray();
            $user_bean = 0;
            $anchor_bean = 0;
            $box_id = request()->get('box_id');
            $box = false;
            if ($box_id){
                $box = Box::find($box_id);
            }
            foreach ($contains as $contain){
                if ($contain['real_odds'] > 0){
                    $user_bean += $contain['real_odds'] * $contain['bean'];
                }
                if ($contain['anchor_odds'] > 0){
                    $anchor_bean += $contain['anchor_odds'] * $contain['bean'];
                }
            }

            $box_user_bean = 0;
            $box_anchor_bean = 0;
            if ($box){
                $box_user_bean = bcmul($query->sum('real_odds'),$box->bean,2);
                $box_anchor_bean = bcmul($query->sum('anchor_odds'),$box->bean,2);
            }
            $boxName = $box->name ?? '';
            return  '<b>宝箱名称：'.$boxName.'<br>用户：奖池<b>'.$query->sum('real_odds').'</b>件，奖池总价 <b>'.$user_bean.'</b>，每轮花费<b>'.$box_user_bean.'</b>，每轮盈利：<b>'.bcsub($box_user_bean,$user_bean,2).'</b><br>主播：奖池<b>'.$query->sum('anchor_odds').'</b>件，奖池总价 <b>'.$anchor_bean.'</b>，每轮花费<b>'.$box_anchor_bean.'</b>，每轮盈利：<b>'.bcsub($box_anchor_bean,$anchor_bean,2).'</b>';
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
        $show = new Show(BoxContain::findOrFail($id));
        $show->panel()->tools(function ($tools) {
            $tools->disableList();
        });
        $show->field('id', __('Id'));
        $show->field('box.name', '所属宝箱');
        $show->field('skins.name', __('饰品名称'))->as(function (){
            if ($this->skins->dura === 0){
                return $this->skins->name;
            }
            return $this->skins->name .' ('. Skins::$fields['dura'][$this->skins->dura] .')';
        });
        $show->field('odds', __('Odds'));
        $show->field('real_odds', __('Real odds'));
        $show->field('anchor_odds', __('Anchor odds'));
        $show->field('is_luck', __('Is luck'))->using(BoxContain::$fields['is_luck']);
        $show->field('luck_odds', __('Luck odds'));
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
        $form = new Form(new BoxContain());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            $tools->disableView();
            $box_id = request()->get('box_id');
            if (empty($box_id) && request()->route()->parameter('box_contain')){
                $contain = BoxContain::find(request()->route()->parameter('box_contain'));
                $box_id = $contain->box_id;
            }
            $tools->add('<a href="'.'/'.config('admin.route.prefix').'/box-contains?box_id=' . $box_id .'" class="btn btn-sm btn-default" title="列表"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>');
        });

        $form->select('box_id', '宝箱')->options(Box::getList())->default(function (){
            return request()->get('box_id',null);
        })->required();
        //$form->select('skin_id', '饰品')->options(Skins::getList())->required();
        $form->select('skin_id', '饰品')->required()->options(function ($id) {
            $skins = Skins::find($id);
            if ($skins) {
                return [$skins->id => $skins->name.' ('. Skins::$fields['dura'][$skins->dura] .')'];
            }
        })->ajax('/'.config('admin.route.prefix').'/api/skins');
        $form->select('level','颜色')->options(BoxContain::$fields['lv'])->default(1);
        //->creationRules(['required', "unique:box_contains,a_id,NULL,id,name,{$name}"], ['unique' => '数据已存在1'])
        //->updateRules();
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
        $form->number('real_odds', __('Real odds'))->default(0)->rules([
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
        $form->number('anchor_odds', __('Anchor odds'))->default(0)->rules([
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
        $form->radioCard('is_luck', __('Is luck'))->options(BoxContain::$fields['is_luck'])->default(0)->required();
        $form->number('luck_odds', __('Luck odds'))->default(0)->rules([
            'required',
            'integer',
            'min:0',
            'max:10000',
        ], [
            'required' => '请输入幸运爆率',
            'integer' => '请输入整数',
            'min' => '数值不能为负数',
            'max' => '数值超出限制最大支持10000',
        ]);
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
            //拿到Cache原子锁 最多锁十秒
            $cacheLock = \Cache::lock(Box::$fields['cacheKey'][2].$form->model()->box_id);
            $cacheAnchorLock = \Cache::lock(Box::$fields['cacheKey'][3].$form->model()->box_id);
            $cacheWelfareLock = \Cache::lock(Welfare::$fields['cacheKey'][0].$form->model()->box_id);
            $cacheWelfareAnchorLock = \Cache::lock(Welfare::$fields['cacheKey'][1].$form->model()->box_id);
            try{
                //10秒内拿不到锁抛出异常
                $cacheLock->block(10);
                $cacheAnchorLock->block(10);
                $cacheWelfareLock->block(10);
                $cacheWelfareAnchorLock->block(10);
                //业务逻辑

                //清理宝箱详情缓存
                \Cache::delete(Box::$fields['cacheKey'][8].$form->model()->box_id);

                //清理用户爆率
                if ($form->real_odds !== null && $form->model()->box_id){
                    Redis::del(Box::$fields['cacheKey'][4].$form->model()->box_id);
                }
                //清理主播爆率
                if ($form->anchor_odds !== null && $form->model()->box_id){
                    Redis::del(Box::$fields['cacheKey'][5].$form->model()->box_id);
                }

                //清理幸运开箱爆率
                if (($form->luck_odds !== null || $form->is_luck !== null) && $form->model()->box_id){
                    Redis::del(Box::$fields['cacheKey'][6].$form->model()->box_id);
                    Redis::del(Box::$fields['cacheKey'][7].$form->model()->box_id);
                }

                //清理福利箱爆率
                Redis::del(Welfare::$fields['cacheKey'][2].$form->model()->box_id);
                Redis::del(Welfare::$fields['cacheKey'][3].$form->model()->box_id);

                //保存后跳转
                $editable = request()->post('_editable');
                $edit_inline = request()->post('_edit_inline');
                $box_id = request()->get('box_id',null);
                if ($form->model()->box_id){
                    $box_id = $form->model()->box_id;
                }
                //释放锁
                $cacheLock->release();
                $cacheAnchorLock->release();
                $cacheWelfareLock->release();
                $cacheWelfareAnchorLock->release();

                if ($editable === null && $edit_inline !== 'true'){
                    return redirect('/'.config('admin.route.prefix').'/box-contains?box_id=' . $box_id);
                }
            } catch (\Exception $e){
                //释放锁
                $cacheLock->release();
                $cacheAnchorLock->release();
                $cacheWelfareLock->release();
                $cacheWelfareAnchorLock->release();
                $error = new MessageBag([
                    'title'   => '程序处理异常',
                    'message' => $e->getMessage(),
                ]);
                return back()->with(compact('error'));
            }
        });
        return $form;
    }
}
