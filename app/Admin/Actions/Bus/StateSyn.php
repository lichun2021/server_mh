<?php


namespace App\Admin\Actions\Bus;

use App\BoxRecord;
use App\Services\SkinsBusService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Class stateSyn
 * @package App\Admin\Actions\Bus
 * @author 春风 <860646000@qq.com>
 */
class StateSyn extends RowAction
{
    public $name = '同步状态';

    public function handle(Model $model)
    {
        $res = SkinsBusService::orderDetail($model->order_id);
        if ($res['code'] != 0){
            throw new \Exception($res['msg'], -1);
        }
        $data = $res['data'];

        if ($data['status'] == 11 || $data['status'] == 8) {
            //11 下单中 || 8 待发送报价
            $model->zbt_status = 1;
            $model->save();
        } elseif ($data['status'] == 9) {
            //9 待接受报价
            $model->zbt_status = 3;
            if ($model->save()) {
                $box_record = BoxRecord::where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record) {
                    $box_record->status = 6;
                    $box_record->save();
                }
            }
        } elseif ($data['status'] == 10) {
            //10 交易完成
            $model->zbt_status = 10;
            if ($model->save()) {
                $box_record = BoxRecord::where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record) {
                    $box_record->status = 1;
                    $box_record->save();
                }
            }
        } elseif ($data['status'] == 7) {
            //7 已退款
            $model->zbt_status = 11;
            //$model->refund_reason = $data['refund_reason'];
            if ($model->save()) {
                $box_record = BoxRecord::where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record) {
                    $box_record->status = 4;
                    $box_record->back_message = $data['refund_reason'];
                    $box_record->save();
                }
            }
        }
        return $this->response()->success('操作成功')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定'. $this->name . '？');
    }
}
