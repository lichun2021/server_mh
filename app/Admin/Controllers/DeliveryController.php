<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/30
 * Time: 21:55
 */

namespace App\Admin\Controllers;

use App\Skins;
use App\BoxRecord;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Admin\Actions\Delivery\ZbtList;
use App\Admin\Actions\V5Item\CreateOrder;
use App\Admin\Actions\Delivery\SendBack;
use App\Admin\Actions\Delivery\Complete;
use App\Admin\Actions\Delivery\YouPinList;
use Encore\Admin\Controllers\AdminController;

class DeliveryController extends AdminController
{
    protected $title = '提货管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BoxRecord());
        $grid->disableCreateButton();
        //禁用导出数据按钮
        $grid->disableExport();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();
        //筛选
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
                $filter->like('user.name', '申请用户');
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('user.mobile', '手机号');
            });
        });

        $grid->actions(function ($actions) {
            //去掉操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();

            $actions->add(new CreateOrder());
            $actions->add(new YouPinList());
            //$actions->add(new ZbtList());
            $actions->add(new SendBack());
            $actions->add(new Complete());
        });
        $grid->model()->where('status', 4)->orderBy('id', 'DESC');
        $grid->column('id', __('Id'));
        $grid->column('user.name', __('申请用户'))->link(function (){
            if ($this->user){
                return $this->user->steam_url;
            }
            return null;
        });
        $grid->column('用户类型')->display(function () {
            return !empty($this->user->anchor) ? '<span class="label label-danger" title="网站主播用户，可直接设为已提货!">主播</span>' : '<span class="label label-success" title="网站正常用户">用户</span>';
        });
        $grid->column('name', '饰品名称');
        $grid->column('cover', '饰品图片')->image('', 75);
        $grid->column('dura', '外观')->using(Skins::$fields['dura']);
        $grid->column('code', '订单号');
        $grid->column('bean', '价格');
        $grid->column('status', '状态')->using(BoxRecord::$fields['status'])->dot([
            0 => 'primary',
            1 => 'success',
            2 => 'warning',
            3 => 'info',
            4 => 'danger',
        ], 'warning');
        $grid->column('back_message', '回调消息');
        $grid->column('updated_at', '下单时间');

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BoxRecord());

        $form->number('user_id', __('User id'));
        $form->number('box_id', __('Box id'));
        $form->text('box_name', __('Box name'));
        $form->number('box_bean', __('Box bean'));
        $form->number('box_award_id', __('Box award id'));
        $form->text('name', __('Name'));
        $form->image('cover', __('Cover'));
        $form->switch('dura', __('Dura'));
        $form->switch('lv', __('Lv'))->default(1);

        return $form;
    }
}
