<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/20
 * Time: 22:22
 */

namespace App\Services;

use App\Box;
use App\BoxContain;
use App\GameArenaBox;
use App\GameAwardRecord;
use App\Skins;
use App\User;
use App\Welfare;
use Illuminate\Support\Facades\Redis;
class BoxService
{
    /**
     * 生成宝箱抽奖列表
     * @param $box_id int 宝箱ID
     * @return array
     * @throws \Exception
     */
    public static function getList($box_id)
    {
        $data = [];
        $ip = request()->ip();
        $box_contains = BoxContain::where('box_id', $box_id)->get()->toArray();
        //通过真实爆率生成物品列表
        foreach ($box_contains as $box_contain) {
            if ((auth('api')->user() === null && $ip === '127.0.0.1') || auth('api')->user()->anchor == 1) {
                for ($i = 0; $i < $box_contain['anchor_odds']; $i++) {
                    $data[] = $box_contain['skin_id'];
                }
            } else {
                for ($i = 0; $i < $box_contain['real_odds']; $i++) {
                    $data[] = $box_contain['skin_id'];
                }
            }
        }
        if (empty($data)){
            throw new \Exception('此宝箱正在维护，请更换其他宝箱，给您带来不便请谅解！',-1);
        }
        shuffle($data);
        return $data;
    }


    /**
     * 生成幸运值抽奖列表
     * @param $id int 宝箱Id
     * @return array
     * @throws \Exception
     */
    public static function getBoxLuckyList($box_id)
    {
        $data = [];
        $box_contains = BoxContain::where('box_id', $box_id)->where('is_luck', 1)->get()->toArray();

        foreach ($box_contains as $box_contain) {
            for ($i = 0; $i < $box_contain['luck_odds']; $i++) {
                $data[] = $box_contain['skin_id'];
            }
        }
        if (empty($data)){
            throw new \Exception('此宝箱正在维护，请更换其他宝箱，给您带来不便请谅解！',-1);
        }
        shuffle($data);
        return $data;
    }

    /**
     * 获得物品
     * @param $box_id int 宝箱Id
     * @return mixed
     * @throws \Exception
     */
    public static function getBoxSkins($box_id)
    {
        $key = self::getCacheKey($box_id);
        $methods = ['rpop','lpop'];
        $randInt = array_rand($methods, 1);
        $method = $methods[$randInt];
        $skinId = Redis::$method($key);
        if ($skinId === false){
            Redis::lpush($key,...self::getList($box_id));
            return self::getBoxSkins($box_id);
        }
        return $skinId;
    }

    /**
     * 获得幸运物品
     * @param $box_id
     * @return mixed
     * @throws \Exception
     */
    public static function getBoxLuckSkins($box_id)
    {
        $key = self::getCacheKey($box_id,true);
        $methods = ['rpop','lpop'];
        $randInt = array_rand($methods, 1);
        $method = $methods[$randInt];
        $skinId = Redis::$method($key);
        if ($skinId === false){
            Redis::lpush($key,...self::getBoxLuckyList($box_id));
            return self::getBoxLuckSkins($box_id);
        }
        return $skinId;
    }

    /**
     * 生成战损抽奖列表
     * @param integer $id
     * @param float $start_bean
     * @param float $end_bean
     * @return array
     */
    public static function getWarList($id, $start_bean, $end_bean)
    {
        $data = [];
        $box_awards = BoxAward::query()->where(['box_id' => $id, 'lv' => 1])
            ->where('bean', '>=', $start_bean)
            ->where('bean', '<=', $end_bean)->get()
            ->toArray();
        foreach ($box_awards as $box_award) {
            $data[] = $box_award['id'];
        }
        return $data;
    }

    /**
     * 获得对战物品
     * @param integer $box_id 宝箱Id
     * @param integer $anchor 主播 1 是 0 否
     * @return mixed
     * @throws \Exception
     */
    public static function getBoxGameArenaSkins($box_id,$anchor)
    {
        $key = self::getGameCacheKey($box_id,$anchor);
        $methods = ['rpop','lpop'];
        $randInt = array_rand($methods, 1);
        $method = $methods[$randInt];
        $skinId = Redis::$method($key);
        if ($skinId === false){
            Redis::lpush($key,...self::getBoxGameArenaList($box_id,$anchor));
            return self::getBoxGameArenaSkins($box_id,$anchor);
        }
        return $skinId;
    }

    /**
     * 获得福利宝箱物品
     * @param $box_id int 宝箱Id
     * @return mixed
     * @throws \Exception
     */
    public static function getWelfareBoxSkins($box_id)
    {
        $key = self::getWelfareCacheKey($box_id);
        $methods = ['rpop','lpop'];
        $randInt = array_rand($methods, 1);
        $method = $methods[$randInt];
        $skinId = Redis::$method($key);
        if ($skinId === false){
            Redis::lpush($key,...self::getList($box_id));
            return self::getWelfareBoxSkins($box_id);
        }
        return $skinId;
    }

    /**
     * 生成对战抽奖列表
     * @param $box_id
     * @param integer $anchor 主播 1 是 0 否
     * @return array
     * @throws \Exception
     */
    public static function getBoxGameArenaList($box_id,$anchor)
    {
        $box_contains = BoxContain::select(['id', 'skin_id', 'game_odds', 'game_anchor_odds'])
            ->where(['box_id' => $box_id, 'is_game' => 1])
            ->get()
            ->toArray();
        foreach ($box_contains as $box_contain) {
            if ($anchor == 1) {
                for ($i = 0; $i < $box_contain['game_anchor_odds']; $i++) {
                    $data[] = $box_contain['skin_id'];
                }
            } else {
                for ($i = 0; $i < $box_contain['game_odds']; $i++) {
                    $data[] = $box_contain['skin_id'];
                }
            }
        }
        if (empty($data)){
            throw new \Exception('此宝箱正在维护，请更换其他宝箱，给您带来不便请谅解！',-1);
        }
        shuffle($data);
        return $data;
    }

    /**
     * 获取宝箱缓存Key
     * @param $box_id
     * @param bool $luck
     * @return string
     */
    public static function getCacheKey($box_id,$luck = false)
    {
        //根据用户对应Cache Key
        $ip = request()->ip();
        if ((auth('api')->user() === null && $ip === '127.0.0.1') || auth('api')->user()->anchor == 1) {
            //主播爆率KEY
            $key = Box::$fields['cacheKey'][5] . $box_id;
            $luckyKey = Box::$fields['cacheKey'][7] . $box_id;
        } else {
            //普通用户
            $key = Box::$fields['cacheKey'][4] . $box_id;
            $luckyKey = Box::$fields['cacheKey'][6] . $box_id;
        }
        if ($luck){
            return $luckyKey;
        }
        return $key;
    }

    /**
     * 获取对战宝箱缓存Key
     * @param integer $box_id 宝箱Id
     * @param integer $anchor $anchor 主播 1 是 0 否
     * @return string
     */
    public static function getGameCacheKey($box_id,$anchor)
    {
        //根据用户对应Cache Key
        if ($anchor == 1) {
            //主播对战爆率KEY
            $key = GameArenaBox::$fields[3] . $box_id;
        } else {
            //普通用户
            $key = GameArenaBox::$fields[2] . $box_id;
        }
        return $key;
    }

    /**
     * 盲盒对战预开箱
     * @param $game_arena_id
     * @param $user_id
     * @param $gameArenaUser
     * @throws \Exception
     */
    public static function open($game_arena_id, $user_id, $gameArenaUser)
    {
        $gameBox = GameArenaBox::where('game_arena_id', $game_arena_id)
            ->get()
            ->toArray();
        foreach ($gameBox as $box) {
            //原子锁
            $user = User::find($user_id);
            if ($user->anchor == 1) {
                $lockKey = GameArenaBox::$fields[1] . $box['box_id'];
            } else {
                $lockKey = GameArenaBox::$fields[0] . $box['box_id'];
            }

            $cacheLock = \Cache::lock($lockKey, 10);
            try {
                //上锁
                $cacheLock->block(10);
                //获取列表
                $skin_id = self::getBoxGameArenaSkins($box['box_id'],$user->anchor);
                //修复价格错误
                $skins = Skins::select(['id', 'bean'])->where(['id' => $skin_id])->first();
                $gameArenaUser->increment('worth', $skins->bean);
                $gameAward = new GameAwardRecord();
                $gameAward->game_arena_id = $game_arena_id;
                $gameAward->user_id = $user_id;
                $gameAward->box_id = $box['box_id'];
                $gameAward->box_bean = $box['box_bean'];
                $gameAward->award_id = $skins->id;
                $gameAward->award_bean = $skins->bean;
                $gameAward->save();
                //释放锁
                $cacheLock->release();
            } catch (\Exception $e) {
                $cacheLock->release();
                //记录错误
                \Log::error('对战开箱失败', [
                    'Message' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                ]);
                throw new \Exception('创建失败,系统错误！', -1);
            }
        }
    }

    /**
     * 获取福宝箱 缓存Key
     * @param $box_id
     * @return string
     */
    public static function getWelfareCacheKey($box_id)
    {
        //根据用户对应Cache Key
        if (auth('api')->user()->anchor == 1) {
            //主播对战爆率KEY
            $key = Welfare::$fields['cacheKey'][3] . $box_id;
        } else {
            //普通用户
            $key = Welfare::$fields['cacheKey'][2] . $box_id;
        }
        return $key;
    }
}
