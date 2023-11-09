<?php


namespace App\Admin\Actions\Bus;


use App\DeliveryRecord;
use App\Skins;
use App\BoxRecord;
use App\Services\SkinsBusService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

/**
 * Bus 发货
 * Class SendSkins
 * @package App\Admin\Actions\Bus
 * @author 春风 <860646000@qq.com>
 */
class SendSkins extends RowAction
{
    public $name = 'Bus购买发货';

    public function handle(Model $model)
    {
        DB::beginTransaction();
        try{
            $max_price = request()->post('max_price',0);
            if ($max_price == 0){
                $max_price = $model->bean;
            }
            $skins = Skins::query()->find($model->skin_id);
            if (!$skins){
                DB::rollBack();
                return $this->response()->error('饰品信息被删除！');
            }

            $record = BoxRecord::where('id',$model->id)->lockForUpdate()->first();
            if (empty($record) || $record->status != 4){
                DB::rollBack();
                return $this->response()->error('订单不存在或已处理！');
            }
            if (empty($record->user->steam_url)){
                DB::rollBack();
                return $this->response()->error('用户未填写Steam收货链接！');
            }
            $trade_no = date('YmdHis') . random_int(1000, 9999);
            $busRes = SkinsBusService::orderQuickBuy($skins->hash_name,$record->user->steam_url,$max_price,$trade_no);
            if ($busRes['code'] != 0){
                DB::rollBack();
                return $this->response()->error($busRes['msg']);
            }
            $busData = $busRes['data'];
            if ($busData['mode'] == 0){
                $delivery = 1;
            } else {
                $delivery = 2;
            }

            $record->status = 5;
            $record->save();
            $deliveryRecord = new DeliveryRecord();
            $deliveryRecord->user_id = $record->user_id;
            $deliveryRecord->record_id = $record->id;
            $deliveryRecord->record_code = $record->code;
            $deliveryRecord->trade_no = $trade_no;
            $deliveryRecord->price = $busData['price'];
            $deliveryRecord->delivery =  $delivery;
            $deliveryRecord->order_id = $busData['order_no'];
            $deliveryRecord->zbt_status = 1;
            $deliveryRecord->platform = 2;
            $deliveryRecord->save();
            DB::commit();
            return $this->response()->success('购买成功！')->refresh();
        } catch (\Exception $e){
            DB::rollBack();
            return $this->response()->error($e->getMessage());
        }

    }

    public function form()
    {
        $this->text('max_price','购买价格上限')->rules([
            'required',
            'min:0'
        ], [
            'required' => '请输入购买价格上限',
            'min' => '购买价格上限不能为0'
        ])->default(0)->help('为0时表示使用饰品价格');
    }
}
