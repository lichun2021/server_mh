<?php

namespace App\Console\Commands;

use App\BoxRecord;
use App\DeliveryRecord;
use App\Services\YouPinService;
use App\Services\ZbtService;
use App\SystemConfig;
use Illuminate\Console\Command;

class YouPinSendSkins extends Command
{
    protected $signature = 'youpin:send-skins';

    protected $description = '有品自动发货';

    public function handle()
    {
        $config = SystemConfig::query()->whereIn('code', ['is_send_skins_bot', 'send_skins_max_bean'])->pluck('value', 'code')->toArray();
        if ($config['is_send_skins_bot'] === '1') {
            $requestFrequency = 0;
            BoxRecord::query()->select(['id', 'bean', 'user_id'])->where('status', 4)->chunk(10, function ($records) use ($config, &$requestFrequency) {
                foreach ($records as $record) {
                    if ($record->bean <= $config['send_skins_max_bean'] && !empty($record->user->steam_url) && $record->user->anchor === 0) {
                        \DB::beginTransaction();
                        try {
                            $model = BoxRecord::query()->where('id', $record->id)->lockForUpdate()->first();
                            if ($model->status === 4 && !empty($model->skins->template_id)) {
                                //生成单号
                                $trade_no = date('YmdHis') . random(4, true);
                                $res = YouPinService::byTemplateCreateOrder($trade_no, $record->user->steam_url, $model->skins->template_id, $model->bean);
                                $requestFrequency++;
                                if ($res['code'] !== 0 || $res['data']['orderStatus'] === 2) {
                                    \DB::commit();
                                    continue;
                                }

                                $model->status = 5;
                                $model->save();
                                $deliveryRecord = new DeliveryRecord();
                                $deliveryRecord->user_id = $model->user_id;
                                $deliveryRecord->record_id = $model->id;
                                $deliveryRecord->record_code = $model->code;
                                $deliveryRecord->trade_no = $trade_no;
                                $deliveryRecord->price = $res['data']['payAmount'];
                                $deliveryRecord->delivery = 2;
                                $deliveryRecord->order_id = $res['data']['orderNo'];
                                $deliveryRecord->platform = 3;
                                $deliveryRecord->save();
                            }
                            \DB::commit();
                        } catch (\Exception $e) {
                            \DB::rollBack();
                            //触发异常 正常保存订单
                            //写日志
                            \Log::error('有品自动发货饰品错误：' . $e->getMessage(), [
                                'File' => $e->getFile(),
                                'Line' => $e->getLine()
                            ]);
                        }
                    }
                    //请求频次
                    if ($requestFrequency >= 5) {
                        $requestFrequency = 0;
                        sleep(1);
                    }
                }
            });
        }
    }
}
