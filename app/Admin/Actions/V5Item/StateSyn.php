<?php

namespace App\Admin\Actions\V5Item;

use App\BoxRecord;
use App\DeliveryRecord;
use App\Services\V5ItemService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StateSyn
 * NameSpace App\Admin\Actions\V5Item
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2023/8/9
 * Time：16:47
 */
class StateSyn extends RowAction
{
    public $name = '同步状态';

    public function handle(Model $model)
    {
        $res = V5ItemService::queryOrderStatus($model->trade_no);
        if ($res['code'] != 0) {
            throw new \Exception($res['msg'], -1);
        }
        try {
            \DB::beginTransaction();
            $model = DeliveryRecord::query()->where('id', $model->id)->lockForUpdate()->first();
            $data = $res['data'];
            if ($data['status'] == 0 || $data['status'] == 1 || (array_key_exists('deliverStatus', $data) && $data['deliverStatus'] == 1) || (array_key_exists('deliverStatus', $data) && $data['deliverStatus'] == 2)) {
                //11 下单中 || 8 待发送报价
                $model->zbt_status = 1;
                $model->save();
            } elseif ($data['status'] == 2 && $data['deliverStatus'] == 3) {
                //9 待接受报价
                $model->zbt_status = 3;
                if ($model->save()) {
                    $box_record = BoxRecord::where('id', $model->record_id)->lockForUpdate()->first();
                    if ($box_record) {
                        $box_record->status = 6;
                        $box_record->save();
                    }
                }
            } elseif ($data['status'] == 3 && $data['deliverStatus'] == 4) {
                //10 交易完成
                $model->zbt_status = 10;
                if ($model->save()) {
                    $box_record = BoxRecord::where('id', $model->record_id)->lockForUpdate()->first();
                    if ($box_record) {
                        $box_record->status = 1;
                        $box_record->save();
                    }
                }
            } elseif ($data['status'] == 4) {
                //7 已退款
                $model->zbt_status = 11;
                //$model->refund_reason = $data['refund_reason'];
                if ($model->save()) {
                    $box_record = BoxRecord::where('id', $model->record_id)->lockForUpdate()->first();
                    if ($box_record && $box_record->status !== 1) {
                        $box_record->status = 0;
                        $box_record->back_message = $data['statusMsg'];
                        $box_record->save();
                    }
                }
            }
            \DB::commit();
            return $this->response()->success('操作成功')->refresh();
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->response()->error($e->getMessage());
        }

    }

    public function dialog()
    {
        $this->confirm('确定' . $this->name . '？');
    }
}
