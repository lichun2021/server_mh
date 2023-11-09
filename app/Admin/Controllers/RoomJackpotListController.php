<?php

namespace App\Admin\Controllers;

use App\RoomJackpot;
use App\RoomJackpotsList;
use App\Skins;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RoomJackpotListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '奖池奖品';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RoomJackpotsList());

        $grid->disableCreateButton();
        $grid->tools(function ($tools) {
            $tools->append("<a href='" . '/' . config('admin.route.prefix') . '/room-jackpots-list/create?jackpot_id=' . request()->get('jackpot_id') . "' class='btn btn-sm btn-success' style='float: right;'><i class='fa fa-plus'></i>&nbsp;&nbsp;新增</a>");
        });
        /*$grid->filter(function($filter){
            // 在这里添加字段过滤器
            $filter->disableIdFilter();
            $filter->like('name', '奖池名称');
        });*/
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->model()
            ->select(['room_jackpots_list.*','skins.id as skins_id','skins.bean'])
            ->Join('skins','skins.id','=','room_jackpots_list.skin_id')
            ->where('jackpot_id',request()->get('jackpot_id'))
            ->orderByDesc('skins.bean')
            ->orderByDesc('id');

        $grid->column('id', __('Id'));
        $grid->column('jackpot.name', '所属奖池');
        $grid->column('skins.cover', '饰品图')->lightbox(['width' => 50]);
        $grid->column('skins.name', '饰品名称')->display(function (){
            if ($this->skins->dura === 0){
                return $this->skins->name;
            }
            return $this->skins->name .' ('. Skins::$fields['dura'][$this->skins->dura] .')';
        });
        $grid->column('bean', getConfig('bean_name'))->sortable();
        $grid->column('num', '数量')->editable();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->header(function ($query) {
            $list = $query->get()->toArray();
            $total_bean = 0;
            foreach ($list as $item){
                if ($item['num'] > 0){
                    $total_bean += $item['num'] * $item['bean'];
                }
            }
            return '奖池统计：<b>'.$query->sum('num').'</b>件饰品，奖池总价 <b>'.$total_bean.'</b>';
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
        $show = new Show(RoomJackpotsList::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('jackpot_id', __('Jackpot id'));
        $show->field('skin_id', __('Skin id'));
        $show->field('num', __('Num'));
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
        $form = new Form(new RoomJackpotsList());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            $tools->disableView();
            $jackpot_id = request()->get('jackpot_id');
            if (empty($jackpot_id) && request()->route()->parameter('room_jackpots_list')) {
                $jackpot = RoomJackpotsList::find(request()->route()->parameter('room_jackpots_list'));
                $jackpot_id = $jackpot->jackpot_id;
            }
            $tools->add('<a href="' . '/' . config('admin.route.prefix') . '/room-jackpots-list?jackpot_id=' . $jackpot_id . '" class="btn btn-sm btn-default" title="列表"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>');
        });

        $form->select('jackpot_id', '所属奖池')->options(RoomJackpot::query()->pluck('name','id'))->default(request()->get('jackpot_id',null));
        $form->select('skin_id', '饰品')->ajax('/'.config('admin.route.prefix').'/api/snatch-skins');
        $form->number('num', '数量')->default(1);
        //保存后回调
        $form->saved(function (Form $form) {
            //保存后跳转
            $editable = request()->post('_editable');
            $edit_inline = request()->post('_edit_inline');
            $jackpot_id = request()->get('jackpot_id', null);
            if ($form->model()->jackpot_id) {
                $jackpot_id = $form->model()->jackpot_id;
            }
            if ($editable === null && $edit_inline !== 'true') {
                return redirect('/' . config('admin.route.prefix') . '/room-jackpots-list?jackpot_id=' . $jackpot_id);
            }
        });

        return $form;
    }
}
