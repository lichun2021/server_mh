<?php

namespace App\Admin\Actions\YouPin;

use App\BoxRecord;
use App\YouPinApi;
use App\Services\YouPinService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class StateSyn extends RowAction
{
    public $name = '同步状态';

    public function handle(Model $model)
    {
        $res = YouPinService::orderStatus($model->order_id);
        if ($res['code'] !== 0) {
            throw new \Exception($res['msg'], -1);
        }
        $data = $res['data'];

        if ($data['bigStatus'] === 140 && in_array($data['smallStatus'], [1101, 1102, 1104, 1106, 1107, 1109])) {
            //1 等待发货, 现在状态1也会推送，意味着这笔订单购买成功了
            $model->zbt_status = 1;
            $model->save();
        } elseif ($data['bigStatus'] === 140 && in_array($data['smallStatus'], [1103])) {
            //3 等待收货，意味着可以通知你们平台的用户去接受报价了
            $model->zbt_status = 3;
            if ($model->save()) {
                $box_record = BoxRecord::query()->where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record) {
                    $box_record->status = 6;
                    $box_record->save();
                }
            }
        } elseif ($data['bigStatus'] === 340 && in_array($data['smallStatus'], [1301, 1302])) {
            //10 成功
            $model->zbt_status = 10;
            if ($model->save()) {
                $box_record = BoxRecord::query()->where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record) {
                    $box_record->status = 1;
                    $box_record->save();
                }
            }
        } elseif ($data['bigStatus'] === 280) {
            //11 订单取消或订单失败
            $model->zbt_status = 11;
            if ($model->save()) {
                $box_record = BoxRecord::query()->where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record && $box_record->status !== 1) {
                    $box_record->status = 0;
                    $box_record->back_message = YouPinApi::$fields['orderSubStatus'][$data['smallStatus']];
                    $box_record->save();
                }
            }
        }
        return $this->response()->success('操作成功')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定' . $this->name . '？');
    }
}
