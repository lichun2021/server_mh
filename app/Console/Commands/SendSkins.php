<?php

namespace App\Console\Commands;

use App\BoxRecord;
use App\DeliveryRecord;
use App\Services\ZbtService;
use App\SystemConfig;
use Illuminate\Console\Command;

class SendSkins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-skins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '饰品自动发货';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $config = SystemConfig::query()->whereIn('code', ['is_send_skins_bot', 'send_skins_max_bean'])->pluck('value', 'code')->toArray();
        if ($config['is_send_skins_bot'] === '1') {
            BoxRecord::query()->select(['id', 'bean', 'user_id'])->where('status', 4)->chunk(10, function ($records) use ($config) {
                foreach ($records as $record) {
                    if ($record->bean <= $config['send_skins_max_bean'] && !empty($record->user->steam_url) && $record->user->anchor === 0) {
                        \DB::beginTransaction();
                        try {
                            $model = BoxRecord::query()->where('id', $record->id)->lockForUpdate()->first();
                            if ($model->status === 4 && !empty($model->skins->item_id)) {
                                //生成单号
                                $trade_no = date('YmdHis') . random_int(1000, 9999);
                                //
                                $res = ZbtService::quick_buy($trade_no, $model->skins->item_id, $model->bean, $record->user->steam_url);
                                if ($res['code'] == 0) {
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
                                $deliveryRecord->price = $res['data']['buyPrice'];
                                $deliveryRecord->delivery = $res['data']['delivery'];
                                $deliveryRecord->order_id = $res['data']['orderId'];
                                $deliveryRecord->save();
                            }
                            \DB::commit();
                        } catch (\Exception $e) {
                            //触发异常 正常保存订单
                            $model->status = 5;
                            $model->save();
                            $deliveryRecord = new DeliveryRecord();
                            $deliveryRecord->user_id = $model->user_id;
                            $deliveryRecord->record_id = $model->id;
                            $deliveryRecord->record_code = $model->code;
                            $deliveryRecord->trade_no = $trade_no;
                            $deliveryRecord->price = $res['data']['buyPrice'] ?? 0;
                            $deliveryRecord->delivery = $res['data']['delivery'] ?? 0;
                            $deliveryRecord->order_id = $res['data']['orderId'] ?? null;
                            $deliveryRecord->save();
                            \DB::commit();
                            //写日志
                            \Log::error('自动发货饰品错误：' . $e->getMessage(), [
                                'File' => $e->getFile(),
                                'Line' => $e->getLine()
                            ]);
                        }
                    }
                }
            });
        }
    }
}
