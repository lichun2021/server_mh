<?php

namespace App\Admin\Actions\YouPin;

use App\BoxRecord;
use App\YouPinApi;
use App\DeliveryRecord;
use App\Services\YouPinService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Support\Facades\DB;

class Buy extends RowAction
{
    public $name = '购买发货';

    public function handle()
    {
        $commodityId = request()->post('_key');
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
            $trade_no = date('YmdHis') . random(4, true);

            if (empty($record->user->steam_url)) {
                DB::commit();
                return $this->response()->error('用户未填写Steam收货链接！')->redirect('/' . config('admin.route.prefix') . '/delivery');
            }
            $cacheKey = YouPinApi::$fields['boxRecordId'] . $record_id;
            $goodsQueryData = \Cache::get($cacheKey);
            $goodsQueryData = array_column($goodsQueryData, null, 'id');
            if ($goodsQueryData === null || !array_key_exists($commodityId, $goodsQueryData)) {
                DB::commit();
                return $this->response()->error('查询商品列表已超时，请重新查询！')->redirect('/' . config('admin.route.prefix') . '/delivery');
            }

            $goodsInfo = $goodsQueryData[$commodityId];
            $resp = YouPinService::byGoodsIdCreateOrder($trade_no, $record->user->steam_url, $commodityId, $goodsInfo['commodityPrice']);

            if ($resp['code'] !== 0 || $resp['data']['orderStatus'] === 2) {
                DB::commit();
                return $this->response()->error($resp['msg'])->redirect('/' . config('admin.route.prefix') . '/delivery');
            }
            $record->status = 5;
            $record->save();
            $deliveryRecord = new DeliveryRecord();
            $deliveryRecord->user_id = $record->user_id;
            $deliveryRecord->record_id = $record->id;
            $deliveryRecord->record_code = $record->code;
            $deliveryRecord->trade_no = $trade_no;
            $deliveryRecord->price = $resp['data']['payAmount'];
            $deliveryRecord->delivery = 1;
            $deliveryRecord->order_id = $resp['data']['orderNo'];
            $deliveryRecord->platform = 3;
            $deliveryRecord->save();
            DB::commit();
            return $this->response()->success('购买成功!')->redirect('/' . config('admin.route.prefix') . '/delivery');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('手动发货错误：' . $e->getMessage());
            return $this->response()->error($e->getMessage())->redirect('/' . config('admin.route.prefix') . '/delivery');
        }
    }

    public function dialog()
    {
        $this->confirm('确定' . $this->name . '？');
    }
}
