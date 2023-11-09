<?php

namespace App\Console\Commands;

use App\Skins;
use App\Services\YouPinService;
use Illuminate\Console\Command;

class YouPinPriceSync extends Command
{
    protected $signature = 'youpin:price-sync';

    protected $description = '有品饰品格同步';

    public function handle()
    {
        $this->info(date('Y-m-d H:i:s').' 有品饰品价格同步开始');
        $requestFrequency = 0;
        Skins::select(['id', 'hash_name', 'template_id'])->orderBy('id')->chunk(200, function ($skins) use (&$requestFrequency) {
            $requestList = [];
            foreach ($skins as $skin) {
                if (!empty($skin->template_id)) {
                    $requestList[] = [
                        'templateId' => $skin->template_id
                    ];
                }
            }
            $skinsResp = YouPinService::batchGetOnSaleCommodityInfo($requestList);
            if ($skinsResp['code'] !== 0) {
                $msg = '有品饰品价格同步错误，批量查询在售商品价格失败！';
                echo $msg;
                \Log::error($msg, $skinsResp);
                exit(0);
            }

            $skinsItems = $skinsResp['data'];
            foreach ($skinsItems as $skinsItem) {
                $model = Skins::where(['hash_name' => $skinsItem['saleTemplateResponse']['templateHashName']])->first();
                if ($model) {
                    $model->bean = round($skinsItem['saleCommodityResponse']['minSellPrice'] * (getConfig('skins_bean_increase') + 1), 2);
                    $model->save();
                }
            }
            $requestFrequency++;
            //请求频次
            if ($requestFrequency >= 30) {
                $requestFrequency = 0;
                sleep(1);
            }
        });
        $this->call('box-check');
        $this->info(date('Y-m-d H:i:s').' 有品饰品价格同步结束');
    }
}
