<?php

namespace App\Console\Commands;

use App\Skins;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class Collect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ZbtCsGo数据采集';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * https://www.zbt.com/api/gw/steamtrade/sga/item-search/v1/list?appId=730&limit=40&currency_type=&page=1&orderBy=1&appid=730&sellType=1
     * @return int
     */
    public function handle()
    {
        $this->info(date('Y-m-d H:i:s').' 开始饰品采集：');
        $dura = [
            'wearcategory0' => 1,
            'WearCategory0' => 1,
            'wearcategory1' => 2,
            'WearCategory1' => 2,
            'wearcategory2' => 3,
            'WearCategory2' => 3,
            'wearcategory3' => 4,
            'WearCategory3' => 4,
            'wearcategory4' => 5,
            'WearCategory4' => 5,
            'wearcategoryna' => 0,
            'WearCategoryNA' => 0
        ];
        $types = [
            'CSGO_Type_Knife' => 2,
            'CSGO_Type_Pistol' => 3,
            'CSGO_Type_Rifle' => 4,
            'CSGO_Type_SMG' => 5,
            'CSGO_Type_Machinegun' => 7,
            'weapon_nova' => 7,
            'weapon_mag7' => 7,
            'weapon_xm1014' => 7,
            'weapon_sawedoff' => 7,
            'weapon_m249' => 7,
            'weapon_negev' => 7,
            'Type_Hands' => 1,
            'CSGO_Tool_Sticker' => 8,
            'CSGO_Type_other' => 10,
        ];
        $quality = [
            'normal' => null,
            'strange' => 'StatTrak™',
            'tournament' => '纪念品',
            'unusual' => '★',
            'unusual_strange' => '★ StatTrak™',
        ];

        $allName = [
            'csgo_tool_patch',
            'type_customplayer',
            'csgo_type_weaponcase',
            'csgo_type_spray',
            'csgo_type_musickit',
            'csgo_type_collectible',
            'csgo_type_ticket',
            'csgo_tool_gifttag',
            'csgo_tool_name_tagtag',
            'csgo_type_tool',
            'csgo_tool_sticker',
        ];
        //$rarity = array_flip(Skins::$fields['rarity']);
        $rarity = [
            'rarity_common_weapon' => 1,//消费级
            'Rarity_Common_Weapon' => 1,//消费级
            'rarity_uncommon_weapon' => 2,//工业级
            'Rarity_Uncommon_Weapon' => 2,//工业级
            'rarity_rare_weapon' => 3,//军规级
            'Rarity_Rare_Weapon' => 3,//军规级
            'rarity_mythical_weapon' => 4,//受限
            'Rarity_Mythical_Weapon' => 4,//受限
            'rarity_legendary_weapon' => 5,//保密
            'Rarity_Legendary_Weapon' => 5,//保密
            'rarity_ancient_weapon' => 6,//隐秘
            'Rarity_Ancient_Weapon' => 6,//隐秘
            'rarity_common' => 7,//普通级
            'Rarity_Common' => 7,//普通级
            'rarity_rare' => 8,//高级
            'Rarity_Rare' => 8,//高级
            'rarity_ancient' => 9,//非凡
            'Rarity_Ancient' => 9,//非凡
            'rarity_legendary' => 10,//奇异
            'Rarity_Legendary' => 10,//奇异
            'rarity_mythical' => 11,//卓越
            'Rarity_Mythical' => 11,//卓越
            'rarity_contraband' => 12,//违禁
            'Rarity_Contraband' => 12,//违禁
            'rarity_rare_character' => 13,//探员:高级
            'Rarity_Rare_Character' => 13,//探员:高级
            'rarity_mythical_character' => 14,//探员:卓越
            'Rarity_Mythical_Character' => 14,//探员:卓越
            'rarity_legendary_character' => 15,//探员:非凡
            'Rarity_Legendary_Character' => 15,//探员:非凡
            'rarity_ancient_character' => 16,//探员:大师
            'Rarity_Ancient_Character' => 16,//探员:大师
        ];
        //USP 消音版（StatTrak™） | 蓝图 (久经沙场)
        $startPage = 1;
        foreach ($types as $key => $val){
            echo '采集分类：'.$key.PHP_EOL;
            $url = 'https://www.zbt.com/api/gw/steamtrade/sga/item-search/v1/list?appId=730&limit=40&currency_type=&filter=type%3D'.$key.'&appid=730&orderBy=0&type='.$key;
            for ($i = $startPage;$i < 500; $i++){
                echo '采集第：'.$i.'页'.PHP_EOL;
                $urlAll = $url.'&page='.$i.'&sellType=1';
                $res = $this->curl($urlAll);
                unset($urlAll);

                if (empty($res) || $res['success'] === false || $res['errorCode'] > 0){
                    throw new \Exception('采集错误，当前页码:'.$i);
                }
                if (empty($res['data']) || empty($res['data']['list'])){
                    \Log::error('采集数据返回为空，跳出循环',['type' => $key,'page' => $i]);
                    continue 2;
                }
                //解析数据
                foreach ($res['data']['list'] as $item){
                    $shortNameArray = explode('|',$item['itemName']);
                    if(count($shortNameArray) > 1){
                        $shortNameArray[1] = trim(preg_replace('/\(.*?\)/', '',$shortNameArray[1]));
                    }
                    $shortNameArray[0] = trim(preg_replace('/\（'.$item['qualityName'].'\）/', '',$shortNameArray[0]));
                    if ($item['quality'] === 'normal'){
                        if (in_array($item['type'],$allName)){
                            $skinsName = $item['itemName'];
                        } else {
                            if (count($shortNameArray) > 1){
                                $skinsName = empty($item['itemInfo']['weaponName']) ? trim($shortNameArray[0]).' | '.$shortNameArray[1]:$item['itemInfo']['weaponName'].' | '.$shortNameArray[1];
                            } else {
                                \Log::info('没有第二个数组',$item);
                                $skinsName = $item['shortName'];
                            }

                        }
                    } else {
                        if (count($shortNameArray) < 2){
                            $skinsName = $item['itemInfo']['weaponName'].'（'.$quality[$item['quality']].'）';
                        } else {
                            $skinsName = empty($item['itemInfo']['weaponName']) ? trim($shortNameArray[0]).'（'.$quality[$item['quality']].'） | '.$shortNameArray[1]:$item['itemInfo']['weaponName'].'（'.$quality[$item['quality']].'） | '.$shortNameArray[1];
                        }
                    }
                    //$skinsDura = $item['exterior'] == '' || $item['exterior'] == 'WearCategoryNA' ? 0:$dura[$item['exterior']];

                    if ($item['exterior'] == ''){
                        if (in_array($item['type'],$allName)){
                            $skinsDura = 0;
                        } else {
                            $shortNameArray2 = explode('|',$item['itemName']);
                            preg_match('/(?:\()(.*)(?:\))/i',$shortNameArray2[1],$matches);
                            if (count($matches) < 2){
                                $skinsDura = 0;
                            } else {
                                $duras = array_flip(Skins::$fields['dura']);
                                if (isset($duras[$matches[1]])){
                                    $skinsDura = $duras[$matches[1]];
                                } else {
                                    \Log::info('未匹配刀外观',$item);
                                    $skinsDura = 0;
                                }
                            }
                        }
                    } elseif ($item['exterior'] == 'wearcategoryna') {
                        $skinsDura = 6;
                    } else {
                        $skinsDura = $dura[$item['exterior']];
                    }

                    $skinsType = $val;
                    //$model = Skins::query()->where(['name' => $skinsName,'dura' => $skinsDura])->first();
                    $model = Skins::where(['hash_name' => $item['marketHashName']])->first();
                    if ($model){
                        continue;
                        //更新价格
                        /*$model->bean = round($item['price'] * (getConfig('skins_bean_increase') + 1), 2);
                        $model->name = $skinsName;
                        $model->dura = $skinsDura;
                        $model->rarity = empty($item['rarity']) ? 0:$rarity[$item['rarity']];
                        $model->save();*/
                    } else {
                        $model = new Skins();
                        $model->name = $skinsName;
                        $model->hash_name = $item['marketHashName'];
                        $model->item_id = $item['itemId'];
                        $model->cover = $item['imageUrl'];
                        $model->dura = $skinsDura;
                        $model->bean = round($item['price'] * (getConfig('skins_bean_increase') + 1), 2);
                        $model->type = $skinsType;
                        $model->rarity = $rarity[$item['rarity']];
                        $model->save();
                    }

                }
                sleep(5);
            }
        }
        //采集完成执行图片下载
        $this->info(date('Y-m-d H:i:s').' 执行图片下载');
        $this->call('download-skins-image');
        //$this->info(date('Y-m-d H:i:s').' 执行价格同步');
        //$this->call('price-sync');
        //$this->info(date('Y-m-d H:i:s').' 饰品采集运行完毕');
        return 1;
    }

    private function curl($url){
        try{
            $response = Http::timeout(10)->withOptions([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
                ]
            ])->get($url);
            $res = $response->json();
            //\Log::info('记录返回',$res);
            if (array_key_exists('code',$res) || !array_key_exists('success',$res)){
                echo 'Curl重试中.....'.PHP_EOL;
                sleep(10);
                return $this->curl($url);
            }
            return $res;
        }catch (\Exception $e){
            echo 'Curl捕获异常重试中.....'.PHP_EOL;
            echo $e->getMessage().PHP_EOL;
            sleep(10);
            return $this->curl($url);
        }
    }
}
