<?php

namespace App\Services;

use App\Box;
use App\User;
use App\GameArena;
use App\BoxContain;
use App\GameArenaBot;
use App\GameArenaUser;

/**
 * Class GameArenaService
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/3/26
 * Time：19:37
 */
class GameArenaService
{
    /**
     * 随机获取机器人对战
     * @return mixed
     */
    public static function getRandomBot()
    {
        $bots = self::getBots();
        $randKey = array_rand($bots);
        $bot = $bots[$randKey];
        return $bot;
    }

    /**
     * 随机机器人用户id
     * @param array $rid
     * @return mixed
     */
    public static function getRandomUserId($rids = [])
    {
        $userIds = self::getBotUserIds();
        if (empty($rids)) {
            $randKey = array_rand($userIds);
            $userId = $userIds[$randKey];
        } else {
            foreach ($rids as $rid) {
                $key = array_search($rid, $userIds);
                unset($userIds[$key]);
            }
            $randKey = array_rand($userIds);;
            $userId = $userIds[$randKey];
        }
        return $userId;
    }

    /**
     * 获得用户Id以及座位号
     * @param string $bot
     * @return array|false
     */
    public static function getGameArena($bot = false)
    {
        if ($bot) {
            $endTime = getConfig('game_arena_bot_time');
            $gameArena = GameArena::where([['status', '=', 0], ['bot_id', '>', 0]])->first();
        } else {
            $endTime = getConfig('game_arena_bot_user_time');
            $gameArena = GameArena::where(['status' => 0, 'bot_id' => 0])->first();
        }

        if (empty($gameArena)){
            return false;
        }
        $gameArena = $gameArena->toArray();
        if (strtotime($gameArena['created_at']) < time() - $endTime){
            $existingUsers = GameArenaUser::select(['game_arena_id','user_id','seat'])
                ->where('game_arena_id',$gameArena['id'])
                ->get()
                ->toArray();
            $existing_users = [];
            $possess_seat = [];
            foreach ($existingUsers as $existingUser){
                $existing_users[] = $existingUser['user_id'];
                $possess_seat[] = $existingUser['seat'];
            }
            $userId = self::getRandomUserId($existing_users);
            $seatNum = self::getSeat($gameArena['user_num'],$possess_seat);
            return [
                'gId' => $gameArena['id'],
                'userId' => $userId,
                'seatNum' => $seatNum,
            ];
        }
        return false;
    }

    /**
     * 获得可用座位号
     * @param $user_num
     * @param $possess_seat
     * @return int
     */
    public static function getSeat($user_num,$possess_seat)
    {
        $data = [
            2 => [0,1],
            3 => [0,1,2],
            4 => [0,1,2,3],
        ];
        $usable_seat = $data[$user_num];
        foreach ($possess_seat as $seat){
            $key = array_search($seat, $usable_seat);
            unset($usable_seat[$key]);
        }

        $randKey = array_rand($usable_seat);;
        $seatNum = $usable_seat[$randKey];

        return $seatNum;
    }

    /**
     * 获取机器人Id
     * @return array|void
     */
    private static function getBotUserIds()
    {
        $user_ids = getConfig('game_arena_bot_user_ids');
        if (!is_numeric($user_ids)) {
            $user_ids = explode(',', $user_ids);
            if (count($user_ids) < 2) {
                $user_ids = explode('，', $user_ids[0]);
            }
            if (count($user_ids) < 2) {
                echo '对战机器人用户Id配置错误';
                \Log::error('对战机器人用户Id配置错误', $user_ids);
                throw new \Exception('对战机器人用户Id配置错误', -1);
            }
        }
        $usableUserId = [];
        if (is_array($user_ids)) {
            foreach ($user_ids as $user_id) {
                $userExists = User::where('id', $user_id)->exists();
                if ($userExists) {
                    $usableUserId[] = $user_id;
                }
            }
        } else {
            $userExists = User::where('id', $user_ids)->exists();
            if ($userExists) {
                $usableUserId[] = $user_ids;
            }
        }
        if (empty($usableUserId)) {
            echo '对战机器人无有效用户Id';
            \Log::error('对战机器人无有效用户Id', $usableUserId);
            throw new \Exception('对战机器人无有效用户Id', -1);
        }
        return $usableUserId;
    }

    /**
     * 获取可用对战列表
     * @return array
     */
    private static function getBots()
    {
        $botList = GameArenaBot::where(['status' => 1])->get()->toArray();
        foreach ($botList as $bot) {
            $box_ids = [];
            foreach ($bot['boxs'] as $box) {
                $box_ids[] = $box['box_id'];
                $boxInfo = Box::where(['id' => $box['box_id']])->first();
                //检测对战宝箱
                if (!$boxInfo || $boxInfo->is_game === 0) {
                    continue 2;
                }
                //检测对战爆率
                $game_odds = BoxContain::where(['box_id' => $box['box_id'], 'is_game' => 1])->sum('game_odds');
                $game_anchor_odds = BoxContain::where(['box_id' => $box['box_id'], 'is_game' => 1])->sum('game_anchor_odds');
                if ($game_odds < 1 || $game_anchor_odds < 1) {
                    continue 2;
                }
            }
            $bot_list[] = [
                'botId' => $bot['id'],
                'boxIds' => $box_ids,
                'userNum' => $bot['user_num']
            ];
        }
        if (empty($bot_list)) {
            echo '机器人无可用对战列表';
            \Log::error('机器人无可用对战列表', $bot_list);
            throw new \Exception('机器人无可用对战列表', -1);
        }
        return $bot_list;
    }
}
