<?php

namespace App\Console\Commands;

use App\BoxRecord;
use App\DeliveryRecord;
use App\Services\V5ItemService;
use Illuminate\Console\Command;

class V5StateSyn extends Command
{
    protected $signature = 'v5:state-syn';

    protected $description = 'v5item发货状态同步';

    public function handle()
    {
        DeliveryRecord::query()->where('platform', 4)->whereBetween('zbt_status', [1, 3])->chunk(100, function ($records) {
            foreach ($records as $record) {
                $res = V5ItemService::queryOrderStatus($record->trade_no);
                if ($res['code'] != 0) {
                    continue;
                }
                try {
                    \DB::beginTransaction();
                    $model = DeliveryRecord::query()->where('id', $record->id)->lockForUpdate()->first();
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
                } catch (\Exception $e) {
                    \DB::rollBack();
                    \Log::error('V5订单状态同步错误：' . $e->getMessage(), [
                        $e->getFile(),
                        $e->getLine(),
                        $e->getMessage()
                    ]);
                }
            }
        });
    }
}
