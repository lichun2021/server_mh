<?php

namespace App\Http\Controllers;

use App\BoxRecord;
use App\DeliveryRecord;
use App\Jobs\SendSmsNoticeJob;
use App\Services\YouPinService;
use App\YouPinApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YouPinCallbackController extends Controller
{
    /**
     * @return array|string
     */
    public function notify()
    {
        $data = request()->post();
        \Log::info('有品调通知来了', $data);
        if (!isset($data['sign']) || !isset($data['callBackInfo']) || !isset($data['messageNo'])) {
            return 'fail';
        }
        //验证签名防止伪造通知
        if (YouPinService::verifySignature()) {
            $callBackInfo = json_decode($data['callBackInfo'], true);
            //return YouPinService::response('成功',$data['messageNo'],true);
            try {
                DB::transaction(function () use ($callBackInfo) {
                    $delivery_record = DeliveryRecord::query()->where('trade_no', $callBackInfo['merchantOrderNo'])->lockForUpdate()->first();
                    if (!$delivery_record) {
                        Log::info('记录不存在：', $callBackInfo);
                        throw new \Exception('记录不存在：' . $callBackInfo['merchantOrderNo']);
                    }
                    $notifyType = $callBackInfo['notifyType'];
                    $orderStatus = $callBackInfo['orderStatus'];
                    $orderSubStatus = $callBackInfo['orderSubStatus'];
                    if ($notifyType === 1 && $orderStatus === 140 && $orderSubStatus === 1101) {
                        //1 等待发货
                        $delivery_record->zbt_status = 1;
                        $delivery_record->save();
                    } elseif ($notifyType === 2 && $orderStatus === 140 && $orderSubStatus === 1103) {
                        //3 等待收货，意味着可以通知你们平台的用户去接受报价了
                        $delivery_record->zbt_status = 3;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::query()->where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record) {
                                if (getConfig('send_sms_notice_switch') === '1' && $box_record->status !== 6) {
                                    SendSmsNoticeJob::dispatch($box_record->user->mobile);
                                }
                                $box_record->status = 6;
                                $box_record->save();
                            }
                        }
                    } elseif ($notifyType === 3 && $orderStatus === 340 && $orderSubStatus === 1301) {
                        //10 成功
                        $delivery_record->zbt_status = 10;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::query()->where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record) {
                                $box_record->status = 1;
                                $box_record->save();
                            }
                        }
                    } elseif ($notifyType === 4 && $orderStatus === 280) {
                        //11 订单取消或订单失败
                        $delivery_record->zbt_status = 11;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::query()->where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record && $box_record->status !== 1) {
                                $box_record->status = 0;
                                $box_record->back_message = YouPinApi::$fields['orderSubStatus'][$orderSubStatus];
                                $box_record->save();
                            }
                        }
                    }
                });
                return YouPinService::response('成功', $data['messageNo'], true);
            } catch (\Exception $e) {
                Log::error('有品回调处理失败', [$e->getMessage(), $e->getFile(), $e->getLine()]);
                return YouPinService::response($e->getMessage(), $data['messageNo'], false);
            }
        }
        Log::error('有品回调签名错误', $data);
        return YouPinService::response('签名错误', $data['messageNo'], false);
    }
}
