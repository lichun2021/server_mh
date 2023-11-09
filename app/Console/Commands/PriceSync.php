<?php


namespace App\Console\Commands;

use App\Skins;
use App\Services\ZbtService;
use Illuminate\Console\Command;

/**
 * Class PriceSync
 * @package App\Console\Commands
 * @author 春风 <860646000@qq.com>
 */
class PriceSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'BUS价格同步';

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
     * @return int
     */
    public function handle()
    {
        $foreachCount = 0;
        Skins::select(['id', 'hash_name'])->orderBy('id')->chunk(200, function ($skins) use (&$foreachCount) {
            $foreachCount++;
            $hashNameArray = [];
            foreach ($skins as $skin) {
                $hashNameArray[] = $skin['hash_name'];
            }
            $res = ZbtService::OpenProductPriceInfo($hashNameArray);
            if (!$res['success']) {
                \Log::info('价格同步返回错误状态码', $res);
                throw new \Exception('价格同步返回错误状态码！');
            }
            $list = $res['data'];
            foreach ($list as $item) {
                $model = Skins::where(['hash_name' => $item['marketHashName']])->first();
                if ($model &&  $item['price'] > 0) {
                    $model->bean = round($item['price'] * (getConfig('skins_bean_increase') + 1), 2);
                    $model->save();
                } else {
                    continue;
                }
            }
            sleep(1);
        });
        echo '总循环 '.$foreachCount.' 轮'.PHP_EOL;
        return 1;
    }
}
