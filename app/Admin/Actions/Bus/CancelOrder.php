<?php


namespace App\Admin\Actions\Bus;

use App\Services\SkinsBusService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CancelOrder
 * @package App\Admin\Actions\Bus
 * @author 春风 <860646000@qq.com>
 */
class CancelOrder extends RowAction
{
    public $name = '取消订单';

    public function handle(Model $model)
    {
        $res = SkinsBusService::orderCancel($model->order_id);
        if ($res['code'] != 0){
            return $this->response()->error($res['msg']);
        }
        sleep(3);
        return $this->response()->success($res['msg'])->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定'. $this->name . '？');
    }
}
