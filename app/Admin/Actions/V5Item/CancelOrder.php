<?php

namespace App\Admin\Actions\V5Item;

use App\Services\V5ItemService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
/**
 * Class CancelOrder
 * NameSpace App\Admin\Actions\V5Item
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2023/8/9
 * Time：16:57
 */
class CancelOrder extends RowAction
{
    public $name = '取消订单';

    public function handle(Model $model)
    {
        $res = V5ItemService::cancelOrder($model->trade_no);
        if ($res['code'] != 0){
            return $this->response()->error($res['msg']);
        } elseif ($res['data']['status'] != 0){
            return $this->response()->error($res['data']['statusMsg']);
        }
        return $this->response()->success('取消订单成功！')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定'. $this->name . '？');
    }
}
