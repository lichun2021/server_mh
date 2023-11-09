<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/11/1
 * Time: 0:32
 */

namespace App\Http\Controllers;

use App\BoxRecord;
use App\DeliveryRecord;
use App\Jobs\SendSmsNoticeJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
//use App\Services\ZbtService;

class ZbtCallbackController extends Controller
{
    /**
     * ZBT回调处理
     * @return string
     * @throws \Throwable
     */
    public function notify()
    {
        //var_dump(ZbtService::open_development_info());die;
        $data = request()->post();
        if (!isset($data['sign'])){
            return 'fail';
        }
        $notifySign = $data['sign'];
        unset($data['sign']);
        $url = $this->ascii($data);
        $url = $url . '&sign=' . config('zbt.appSecret');
        $sign = strtoupper(md5($url));
        //Log::info('ZBT回调来了',$data);
        //验证ZBT签名防止伪造通知
        if ($data['type'] == 0 && $notifySign == $sign) {
            try {
                DB::transaction(function () use ($data) {
                    $delivery_record = DeliveryRecord::query()->where('trade_no', $data['outTradeNo'])->lockForUpdate()->first();
                    if (!$delivery_record) {
                        Log::info('记录不存在：', $data);
                        throw new \Exception("记录不存在：" . $data['outTradeNo']);
                    }
                    if ($data['status'] == 1) {
                        //1 等待发货, 现在状态1也会推送，意味着这笔订单购买成功了
                        $delivery_record->price = $data['payMoney'] ?? 0;
                        $delivery_record->order_id = $data['orderId'] ?? null;
                        $delivery_record->zbt_status = 1;
                        $delivery_record->save();
                    } elseif ($data['status'] == 3) {
                        //3 等待收货，意味着可以通知你们平台的用户去接受报价了
                        $delivery_record->zbt_status = 3;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::query()->where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record) {
                                if(getConfig('send_sms_notice_switch') === '1' && $box_record->status !== 6){
                                    SendSmsNoticeJob::dispatch($box_record->user->mobile);
                                }
                                $box_record->status = 6;
                                $box_record->save();
                            }
                        }
                    } elseif ($data['status'] == 10) {
                        //10 成功
                        $delivery_record->zbt_status = 10;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::query()->where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record) {
                                $box_record->status = 1;
                                $box_record->save();
                            }
                        }
                    } elseif ($data['status'] == 11) {
                        //11 订单取消或订单失败
                        $delivery_record->zbt_status = 11;
                        if ($delivery_record->save()) {
                            $box_record = BoxRecord::query()->where('id', $delivery_record->record_id)->lockForUpdate()->first();
                            if ($box_record && $box_record->status !== 1) {
                                $box_record->status = 0;
                                $box_record->save();
                            }
                        }
                    }
                });
                return 'success';
            } catch (\Exception $e) {
                Log::error('ZBT回调处理失败', [$e->getMessage(),$e->getFile(),$e->getLine()]);
                return 'fail';
            }
        }
        Log::error('ZBT回调签名错误',['ZBT签名：'.$notifySign.' 本站签名：'.$sign]);
        return 'fail';
    }

    /**
     * Ascii 参数排序
     * @param array $params
     * @return string
     */
    private function ascii(array $params)
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $val) {
            $val = $val === null ? 'null' : $val;
            $str .= $k . '=' . $val . '&';
        }
        $strs = rtrim($str, '&');

        return $strs;
    }
}
