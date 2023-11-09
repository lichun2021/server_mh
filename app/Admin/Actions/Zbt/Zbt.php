<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/31 11:05
 */

namespace App\Admin\Actions\Zbt;

use App\DeliveryRecord;
use App\Services\ZbtService;
use App\BoxRecord;
use Encore\Admin\Actions\RowAction;
use Illuminate\Support\Facades\DB;

class Zbt extends RowAction
{
    public $name = '购买发货';

    public function handle($model)
    {
        $zbt_id = request()->post('_key');
        $url = $_SERVER['HTTP_REFERER'];
        $url = parse_url($url);
        $param = $url['query'];
        $item = explode('=', $param);
        $record_id = $item[1];

        \DB::beginTransaction();
        try {
            $record = BoxRecord::query()->where('id', $record_id)->lockForUpdate()->first();
            if (empty($record) || $record->status != 4) {
                DB::commit();
                return $this->response()->error('订单不存在或已处理！')->redirect('/' . config('admin.route.prefix') . '/delivery');
            }
            $trade_no = date('YmdHis') . random_int(1000, 9999);

            if (empty($record->user->steam_url)) {
                DB::commit();
                return $this->response()->error('用户未填写Steam收货链接！')->redirect('/' . config('admin.route.prefix') . '/delivery');
            }

            $zbt = ZbtService::buy($trade_no, $zbt_id, $record->user->steam_url);

            if ($zbt['code'] == 0) {
                DB::commit();
                return $this->response()->error($zbt['message'])->redirect('/' . config('admin.route.prefix') . '/delivery');
            }
            $record->status = 5;
            $record->save();
            $deliveryRecord = new DeliveryRecord();
            $deliveryRecord->user_id = $record->user_id;
            $deliveryRecord->record_id = $record->id;
            $deliveryRecord->record_code = $record->code;
            $deliveryRecord->trade_no = $trade_no;
            $deliveryRecord->price = $zbt['price'];
            $deliveryRecord->delivery = $zbt['delivery'];
            $deliveryRecord->order_id = $zbt['order_id'];
            $deliveryRecord->save();
            DB::commit();
            return $this->response()->success('购买成功!')->redirect('/' . config('admin.route.prefix') . '/delivery');
        } catch (\Exception $e) {
            $record->status = 5;
            $record->save();
            $deliveryRecord = new DeliveryRecord();
            $deliveryRecord->user_id = $record->user_id;
            $deliveryRecord->record_id = $record->id;
            $deliveryRecord->record_code = $record->code;
            $deliveryRecord->trade_no = $trade_no;
            $deliveryRecord->price = $res['data']['buyPrice'] ?? 0;
            $deliveryRecord->delivery = $res['data']['delivery'] ?? 0;
            $deliveryRecord->order_id = $res['data']['orderId'] ?? null;
            $deliveryRecord->save();
            DB::commit();
            \Log::error('手动发货错误：' . $e->getMessage());
            return $this->response()->error($e->getMessage())->redirect('/' . config('admin.route.prefix') . '/delivery');
        }
    }

    public function dialog()
    {
        $this->confirm('确定' . $this->name . '？');
    }
}
