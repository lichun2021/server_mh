<?php

namespace App\Admin\Actions\V5Item;

use App\Skins;
use App\BoxRecord;
use App\DeliveryRecord;
use App\Services\V5ItemService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CreateOrder
 * NameSpace App\Admin\Actions\V5Item
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2023/8/9
 * Time：16:47
 */
class CreateOrder extends RowAction
{
    public $name = 'V5极速发货';

    public function handle(Model $model)
    {
        \DB::beginTransaction();
        try {
            $max_price = request()->post('max_price', 0);
            if ($max_price == 0) {
                $max_price = $model->bean;
            }
            $skins = Skins::query()->find($model->skin_id);
            if (!$skins) {
                \DB::rollBack();
                return $this->response()->error('饰品信息被删除！');
            }

            $record = BoxRecord::where('id', $model->id)->lockForUpdate()->first();
            if (empty($record) || $record->status != 4) {
                \DB::rollBack();
                return $this->response()->error('订单不存在或已处理！');
            }
            if (empty($record->user->steam_url)) {
                \DB::rollBack();
                return $this->response()->error('用户未填写Steam收货链接！');
            }
            $trade_no = date('YmdHis') . random(4, true);
            $resp = V5ItemService::createOrderByMarketHashName($skins->hash_name, $max_price, $record->user->steam_url, $trade_no);
            if ($resp['code'] != 0) {
                \DB::rollBack();
                return $this->response()->error($resp['msg']);
            }
            $data = $resp['data'];

            $record->status = 5;
            $record->save();
            $deliveryRecord = new DeliveryRecord();
            $deliveryRecord->user_id = $record->user_id;
            $deliveryRecord->record_id = $record->id;
            $deliveryRecord->record_code = $record->code;
            $deliveryRecord->trade_no = $trade_no;
            $deliveryRecord->price = $data['payAmount'];
            $deliveryRecord->delivery = 1;
            $deliveryRecord->order_id = $data['orderNo'];
            $deliveryRecord->zbt_status = 1;
            $deliveryRecord->platform = 4;
            $deliveryRecord->save();
            \DB::commit();
            return $this->response()->success('花费 '.$data['payAmount'].'RMB 购买成功！')->refresh();
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->response()->error($e->getMessage());
        }

    }

    public function form()
    {
        $this->text('max_price', '购买价格上限')->rules([
            'required',
            'min:0'
        ], [
            'required' => '请输入购买价格上限',
            'min' => '购买价格上限不能为0'
        ])->default(0)->help('为0时表示使用饰品价格');
    }
}
