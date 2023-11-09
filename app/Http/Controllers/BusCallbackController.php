<?php


namespace App\Http\Controllers;

use App\BoxRecord;
use App\DeliveryRecord;
use App\Services\SkinsBusService;
use Illuminate\Support\Facades\DB;

/**
 * Class BusCallbackController
 * @package App\Http\Controllers
 * @author 春风 <860646000@qq.com>
 */
class BusCallbackController extends Controller
{
    public function notify()
    {
        $data = request()->post();
        //写日志
        \Log::info('Bus回调来了', $data);
        if (!isset($data['sign']) || !isset($data['order_no']) || !isset($data['custom_order_no']) || !isset($data['refund_type']) || !isset($data['status'])) {
            return 'fail';
        }
        $busSign = $data['sign'];

        $busSignArray = SkinsBusService::requestParameterRemovalEmpty($data);
        $localSign = SkinsBusService::getSign($busSignArray);

        if($busSign === $localSign){
            try {
                DB::transaction(function () use ($data) {
                    $delivery_record = DeliveryRecord::query()->where('trade_no', $data['custom_order_no'])->lockForUpdate()->first();
                    if (!$delivery_record) {
                        throw new \Exception('记录不存在：' . $data['custom_order_no']);
                    }
                    //已退款
                    if ($data['status'] == 7){
                        //7 已退款
                        $delivery_record->zbt_status = 11;
                        $delivery_record->refund_reason = $data['refund_reason'];
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record) {
                                $box_record->status = 0;
                                $box_record->back_message = $data['refund_reason'];
                                $box_record->save();
                            }
                        }
                    } elseif ($data['status'] == 9){
                        //9 待接受报价
                        $delivery_record->zbt_status = 3;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record) {
                                $box_record->status = 6;
                                $box_record->save();
                            }
                        }
                    } elseif ($data['status'] == 10){
                        //10 交易完成
                        $delivery_record->zbt_status = 10;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::query()->where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record) {
                                $box_record->status = 1;
                                $box_record->back_message = null;
                                $box_record->save();
                            }
                        }
                    }
                });
                return 'success';
            } catch (\Exception $e) {
                \Log::error('Bus回调处理失败',[$e->getMessage()]);
                return 'fail';
            }
        }
        \Log::error('Bus回调签名验证失败',$data);
        return 'fail';
    }
}
