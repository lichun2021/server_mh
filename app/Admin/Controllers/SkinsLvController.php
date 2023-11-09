<?php


namespace App\Admin\Controllers;

use App\Box;
use App\SkinsLv;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Controllers\AdminController;

/**
 * Class SkinsLvController
 * @package App\Admin\Controllers
 * @author 春风 <860646000@qq.com>
 */
class SkinsLvController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '饰品等级';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SkinsLv());
        //禁用分页
        $grid->disablePagination();
        //禁用查询过滤器
        $grid->disableFilter();
        //禁用行选择器
        $grid->disableColumnSelector();
        //禁用列选择器
        $grid->disableRowSelector();

        $grid->column('id', __('Id'));
        $grid->column('name', __('等级'));
        $grid->column('bg_image', '背景')->image('', 50);
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
        $show = new Show(SkinsLv::findOrFail($id));
        $show->field('id', __('Id'));
        $show->field('name', __('等级名称'));
        $show->field('bg_image', __('背景图片'))->image('', 100);


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SkinsLv());
        $form->text('name', '名称')->required();
        $form->image('bg_image', '背景')->uniqueName()->required()->move('images/lv');
        //保存后执行
        $form->saved(function (Form $form) {
            //清除等级缓存
            Cache::delete(SkinsLv::$fields['cacheKey']);
            $boxs = Box::query()->select(['id'])->get()->toArray();
            foreach ($boxs as $box){
                $key = Box::$fields['cacheKey'][8] . $box['id'];
                Cache::delete($key);
            }
        });

        return $form;
    }
}
