<?php

namespace App\Console\Commands;

use App\Skins;
use Illuminate\Console\Command;
use App\Services\YouPinService;
use Illuminate\Support\Facades\Http;
class Upgrade extends Command
{
    protected $signature = 'upgrade';

    protected $description = '有品饰品平台升级';

    public function handle()
    {
        $templateResp = YouPinService::templateQuery();
        if ($templateResp['code'] !== 0) {
            $msg = '有品饰品采集错误，获取模板Id失败！';
            echo $msg;
            \Log::error($msg, $templateResp);
            exit(0);
        }
        $templateItems = Http::get($templateResp['data'])->json();

        foreach ($templateItems as $templateItem) {
            $model = Skins::where(['hash_name' => $templateItem['hashName']])->first();
            if ($model) {
                $model->template_id = $templateItem['id'];
                $model->save();
            }
        }
    }
}
