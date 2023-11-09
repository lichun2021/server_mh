<?php


namespace App\Admin\Actions\Zbt;

use App\BoxRecord;
use App\Services\ZbtService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class StateSyn extends RowAction
{
    public $name = '同步状态';

    public function handle(Model $model)
    {
        $res = ZbtService::detail($model->order_id);
        if ($res['code'] == 0){
            throw new \Exception($res['message'], -1);
        }
        $data = $res['data'];

        if ($data['status'] == 1 || $data['status'] == 2) {
            //1 等待发货, 现在状态1也会推送，意味着这笔订单购买成功了
            $model->zbt_status = 1;
            $model->save();
        } elseif ($data['status'] == 3) {
            //3 等待收货，意味着可以通知你们平台的用户去接受报价了
            $model->zbt_status = 3;
            if ($model->save()) {
                $box_record = BoxRecord::query()->where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record) {
                    $box_record->status = 6;
                    $box_record->save();
                }
            }
        } elseif ($data['status'] == 10) {
            //10 成功
            $model->zbt_status = 10;
            if ($model->save()) {
                $box_record = BoxRecord::query()->where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record) {
                    $box_record->status = 1;
                    $box_record->save();
                }
            }
        } elseif ($data['status'] == 11) {
            //11 订单取消或订单失败
            $model->zbt_status = 11;
            if ($model->save()) {
                $box_record = BoxRecord::query()->where('id', $model->record_id)->lockForUpdate()->first();
                if ($box_record && $box_record->status !== 1) {
                    $box_record->status = 0;
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
