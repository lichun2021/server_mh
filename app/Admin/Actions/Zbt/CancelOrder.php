<?php


namespace App\Admin\Actions\Zbt;

use App\Services\ZbtService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

/**
 * 取消订单
 * Class CancelOrder
 * @package App\Admin\Actions\Zbt
 * @author 春风 <860646000@qq.com>
 */
class CancelOrder extends RowAction
{
    public $name = '取消订单';

    public function handle(Model $model)
    {
        $endTime = strtotime($model->updated_at) + 1200;
        if ($model->zbt_status !== 1){
            throw new \Exception('订单状态不支持取消操作！', -1);
        } elseif (time() <= $endTime){
            throw new \Exception('下单20分钟后卖家未发货才可许取消订单！', -1);
        }
        $res = ZbtService::orderCancel($model->order_id);
        if ($res['code'] == 0){
            throw new \Exception($res['message'], -1);
        }
        sleep(3);
        return $this->response()->success($res['message'])->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定'. $this->name . '？');
    }
}
