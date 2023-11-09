<?php

namespace App\Admin\Controllers;

use App\Skins;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Actions\Shop\UpperShelf;
use App\Admin\Actions\Shop\LowerShelf;

class ShopController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '饰品商场';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Skins());

        $grid->batchActions(function ($batch) {
            $batch->add(new UpperShelf());
            $batch->add(new LowerShelf());
        });
        //禁用创建按钮
        $grid->disableCreateButton();
        //禁用查询过滤器
        $grid->filter(function ($filter) {
            // 在这里添加字段过滤器
            $filter->column(1/3, function ($filter) {
            });
            $filter->column(1/3, function ($filter) {
                $filter->like('name', '饰品名称');
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('is_shop', '是否上架')->select([0 => '否',1 => '是']);
            });
        });

        $grid->model()->orderByDesc('is_shop')->orderByDesc('id');
        $grid->disableActions();
        $grid->column('id', 'Id')->sortable();
        $grid->column('name', '饰品名称')->sortable();
        $grid->column('cover', '饰品封面')->lightbox(['width' => 75]);
        $grid->column('dura', '外观')->using(Skins::$fields['dura']);
        $grid->column('bean', getConfig('bean_name'))->display(function (){
            $mall_bean_rate = 1 + bcdiv(getConfig('mall_bean_rate'),100,2);
            return bcmul($this->bean,$mall_bean_rate,2);
        })->sortable();
        $grid->column('is_shop', __('是否上架'))->editable('select',[0 => '否',1 => '是'])->sortable();
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
        $show = new Show(Skins::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('box_id', __('Box id'));
        $show->field('name', __('Name'));
        $show->field('cover', __('Cover'));
        $show->field('odds', __('Odds'));
        $show->field('real_odds', __('Real odds'));
        $show->field('anchor_odds', __('Anchor odds'));
        $show->field('game_odds', __('Game odds'));
        $show->field('dura', __('Dura'));
        $show->field('lv', __('Lv'));
        $show->field('bean', __('Bean'));
        $show->field('max_t', __('Max t'));
        $show->field('type', __('Type'));
        $show->field('is_lucky_box', __('Is lucky box'));
        $show->field('is_game', __('Is game'));
        $show->field('clear_lucky', __('Clear lucky'));
        $show->field('shop_inventory', __('Shop inventory'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('deleted_at', __('Deleted at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Skins());
        $form->number('shop_inventory', __('Shop inventory'))->rules([
            'required',
            'integer',
            'min:0'
        ],[
            'required' => '库存不能为空',
            'integer' => '库存必须为整数数值',
            'min' => '库存最小值为0',
        ]);

        $form->switch('is_shop', __('Is shop'));

        return $form;
    }
}
