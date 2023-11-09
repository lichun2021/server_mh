<?php


namespace App\Admin\Actions\LuckySkins;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class UpperShelf
 * @package App\Admin\Actions\LuckySkins
 * @author 春风 <860646000@qq.com>
 */
class UpperShelf extends BatchAction
{
    public $name = '批量上架';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            $model->is_lucky_box = 1;
            $model->luck_interval = request()->get('luck_interval');
            $model->luck_interval_anchor = request()->get('luck_interval_anchor');
            $model->save();
        }
        return $this->response()->success('操作成功！')->refresh();
    }

    public function form()
    {
        $this->text('luck_interval', '幸运区间')->rules(['required', 'regex:/^[0-9]+\/[0-9]+$/'], [
            'required' => '区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 110/200'
        ])->default('110/150')->help('区间开始结束值 如：110/200 110为开始值 200为结束值，结束值必须大于起始值。');;
        $this->text('luck_interval_anchor', '幸运区间(主播)')->rules(['required', 'regex:/^[0-9]+\/[0-9]+$/'], [
            'required' => '区间必须填写',
            'regex' => '格式错误,请输入正确格式！如 110/200'
        ])->default('76/110')->help('主播幸运区间开始结束值 如：110/200 110为开始值 200为结束值，结束值必须大于起始值。');
    }
}
