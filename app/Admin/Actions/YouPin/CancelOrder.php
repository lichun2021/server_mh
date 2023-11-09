<?php

namespace App\Admin\Actions\YouPin;

use App\Services\YouPinService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class CancelOrder extends RowAction
{
    public $name = '取消订单';

    public function handle(Model $model)
    {
        $endTime = strtotime($model->updated_at) + 1800;
        if ($model->zbt_status !== 1) {
            throw new \Exception('订单状态不支持取消操作！', -1);
        } elseif (time() <= $endTime) {
            throw new \Exception('下单30分钟后卖家未发货才可许取消订单！', -1);
        }
        $res = YouPinService::orderCancel($model->order_id);
        if ($res['code'] !== 0 || $res['data']['result'] === 3) {
            throw new \Exception($res['msg'], -1);
        }
        sleep(3);
        return $this->response()->success($res['msg'])->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定' . $this->name . '？');
    }
}
