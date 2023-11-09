<?php

namespace App\Console\Commands;

use App\Box;
use App\Skins;
use App\User;
use App\BoxLucky;
use App\BoxRecord;
use App\BoxContain;
use App\Services\BoxService;
use App\Jobs\TopHistory;
use Illuminate\Console\Command;

class OpenBoxBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'open-box-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动开箱机器人';

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
     */
    public function handle()
    {
        $user_ids = User::where(['is_ob_robot' => 1])->pluck('id')->toArray();
        if (getConfig('is_open_box_bot') == 1 && getConfig('bot_minute_open_box_num') > 0 && count($user_ids) > 0) {

            $box_ids = Box::query()->where(['is_putaway' => 1])->pluck('id')->toArray();
            $contains = BoxContain::select(['box_id', \DB::raw('SUM(real_odds) AS real_odds'), \DB::raw('SUM(anchor_odds) AS anchor_odds')])->whereIn('box_id', $box_ids)->groupBy('box_id')->get()->toArray();

            $box_ids = [];
            foreach ($contains as $contain) {
                if (intval($contain['real_odds']) < 1 || intval($contain['anchor_odds']) < 1) {
                    continue;
                }
                $box_ids[] = $contain['box_id'];
            }
            for ($i = 0; $i < getConfig('bot_minute_open_box_num'); $i++) {
                $box_rand_key = array_rand($box_ids);
                $box_id = $box_ids[$box_rand_key];
                $user_rand_key = array_rand($user_ids);
                $user_id = $user_ids[$user_rand_key];
                //主播原子锁
                $lockKey = Box::$fields['cacheKey'][3] . $box_id;
                //拿到Cache原子锁 最多锁十秒
                $cacheLock = \Cache::lock($lockKey, 10);
                //开启数据库事务
                \DB::beginTransaction();
                try {
                    $box = Box::where(['id' => $box_id])->first();
                    //主播幸运步数
                    $box_lucky_field = 'luck_interval_anchor';
                    $lucky_record_field = 'luck_anchor_value';

                    //10秒内拿不到锁抛出异常
                    $cacheLock->block(10);

                    //查询箱子幸运值记录
                    $lucky_record = BoxLucky::where('box_id', $box_id)->first();
                    //不存在时创建
                    if ($lucky_record === null) {
                        $lucky_record = BoxLucky::createBoxRecord($box);
                    }

                    if ($lucky_record->$lucky_record_field <= 0) {
                        //触发幸运开箱
                        $award_id = BoxService::getBoxLuckSkins($box_id);
                        //重新生成主播幸运值保存
                        $lucky_record->$lucky_record_field = $box->$box_lucky_field;
                        $lucky_record->save();
                    } else {
                        //普通开箱
                        //减幸运值
                        $lucky_record->decrement($lucky_record_field);
                        $award_id = BoxService::getBoxSkins($box_id);
                    }

                    //新修改入库
                    $box_record = new BoxRecord();
                    $skins = Skins::find($award_id);
                    $contain = BoxContain::select('level')->where(['box_id' => $box_id, 'skin_id' => $skins->id])->first();
                    $box_record->get_user_id = $user_id;
                    $box_record->user_id = $user_id;
                    $box_record->box_id = $box->id;
                    $box_record->box_name = $box->name;
                    $box_record->box_bean = $box->bean;
                    $box_record->skin_id = $skins->id;
                    $box_record->name = $skins->name;
                    $box_record->cover = $skins->getRawOriginal('cover');
                    $box_record->dura = $skins->dura;
                    $box_record->lv = $contain->level;
                    $box_record->bean = $skins->bean;
                    $box_record->code = getUniqueOrderNumber();
                    $box_record->type = 1;
                    $box_record->is_purse = $skins->is_purse;
                    $box_record->status = 2;
                    $box_record->save();
                    TopHistory::dispatch([$box_record->id]);
                    //提交事务
                    \DB::commit();
                    //释放锁
                    $cacheLock->release();
                } catch (\Exception $e) {
                    //回滚事务
                    \DB::rollBack();
                    //释放锁
                    $cacheLock->release();
                    \Log::error('开箱机器人运行错误：' . $e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
                }
            }
        }
    }
}
