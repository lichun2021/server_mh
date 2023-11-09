<?php

namespace App\Console\Commands;

use App\Skins;
use App\Services\YouPinService;
use Illuminate\Console\Command;

class YouPinCollect extends Command
{
    protected $signature = 'youpin:collect';

    protected $description = '有品饰品采集';

    private $types = [
        1 => 2,
        21 => 3,
        32 => 4,
        44 => 5,
        52 => 7,
        57 => 7,
        60 => 1,
        95 => 10,
        106 => 8,
    ];

    private $rarityName = [
        '违禁' => 12,
        '隐秘' => 6,
        '保密' => 5,
        '受限' => 4,
        '军规级' => 3,
        '工业级' => 2,
        '消费级' => 1,
        '非凡' => 9,
        '卓越' => 11,
        '奇异' => 10,
        '高级' => 8,
        '普通级' => 7,
        '探员:高级' => 13,
        '探员:卓越' => 14,
        '探员:非凡' => 15,
        '探员:大师' => 16
    ];

    private $duraArray = [
        '(崭新出厂)' => 1,
        '(略有磨损)' => 2,
        '(久经沙场)' => 3,
        '(破损不堪)' => 4,
        '(战痕累累)' => 5,
        '(无涂装)' => 6,
    ];

    public function handle()
    {
        $this->info(date('Y-m-d H:i:s').' 有品饰品采集开始：');
        $requestFrequency = 0;
        foreach ($this->types as $externalType => $localType) {
            for ($i = 0; $i <= 1000; $i++) {
                $page = $i + 1;
                $productsOnSale = YouPinService::queryTemplateSaleByCategory($externalType, $page);
                $requestFrequency++;
                if ($productsOnSale['code'] !== 0) {
                    $msg = '有品饰品采集错误，查询在售商品详情失败！';
                    echo $msg;
                    \Log::error($msg, $productsOnSale);
                    exit(0);
                } elseif (!array_key_exists('saleTemplateByCategoryResponseList', $productsOnSale['data'])) {
                    \Log::info('采集数据返回为空，跳出循环', ['typeId' => $externalType, 'page' => $page]);
                    continue 2;
                }

                $saleSkins = $productsOnSale['data']['saleTemplateByCategoryResponseList'];
                foreach ($saleSkins as $saleSkin) {
                    $model = Skins::where(['hash_name' => $saleSkin['templateHashName']])->exists();
                    if ($model) {
                        continue;
                    } else {
                        if ($saleSkin['rarityName'] === '基础'){
                            continue;
                        } elseif (!array_key_exists('minSellPrice',$saleSkin)){
                            continue;
                        }
                        //获取饰品名称
                        $skinNameArray = explode('|', $saleSkin['templateName']);
                        $arrayCount = count($skinNameArray);
                        if ($arrayCount > 1) {
                            $key = $arrayCount - 1;
                            preg_match_all('/\(.*?\)/', $skinNameArray[$key], $array);
                            if (!empty($array[0]) && array_key_exists($array[0][0], $this->duraArray)) {
                                $skinsName = trim(str_replace($array[0][0], '', $saleSkin['templateName']));
                                $skinsDura = $this->duraArray[$array[0][0]];
                            } else {
                                $skinsName = $saleSkin['templateName'];
                                $skinsDura = 0;
                            }
                        } else {
                            $skinsName = $saleSkin['templateName'];
                            $skinsDura = 0;
                        }
                        //获取饰品品质
                        $rarityKey = $saleSkin['typeHashName'] === 'Other' && $saleSkin['weaponHashName'] === 'Type_CustomPlayer' ? '探员:' . $saleSkin['rarityName'] : $saleSkin['rarityName'];
                        $model = new Skins();
                        $model->name = $skinsName;
                        $model->hash_name = $saleSkin['templateHashName'];
                        $model->template_id = $saleSkin['templateId'];
                        $model->cover = $saleSkin['iconUrl'];
                        $model->dura = $skinsDura;
                        $model->bean = round($saleSkin['minSellPrice'] * (getConfig('skins_bean_increase') + 1), 2);
                        $model->type = $localType;
                        $model->rarity = $this->rarityName[$rarityKey];
                        $model->save();
                    }
                }
                //限速
                if ($requestFrequency >= 10){
                    sleep(1);
                }
            }
        }
        $this->info(date('Y-m-d H:i:s').' 执行图片下载');
        $this->call('download-skins-image');
        $this->info(date('Y-m-d H:i:s').' 有品饰品采集结束');
    }
}
