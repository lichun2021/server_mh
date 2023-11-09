<?php

namespace App\Console\Commands;

use App\User;
use App\Skins;
use App\GameRanking;
use App\BoxRecord;
use App\GameArena;
use App\BoxContain;
use App\GameArenaUser;
use App\GameAwardRecord;
use App\Jobs\TopHistory;
use App\BeanChangeRecord;
use App\Services\BoxService;
use Illuminate\Console\Command;
use App\Services\GameArenaService;
use App\Services\WebSocketMsgPushService;

/**
 * Class PlayGameArena
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/3/27
 * Time：5:50
 */
class PlayGameArena extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'play-game-arena-bot {bot}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '打竞技场机器人';

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
     *
     * @return int
     */
    public function handle()
    {

        $isBot = $this->argument('bot');
        if (!in_array($isBot,[false,true])){
            $this->error('参数错误');
            return 0;
        }

        $res = GameArenaService::getGameArena($isBot);
        if ($res === false){
            return 0;
        }

        try {
            \DB::transaction(function () use ($res) {

                $game_arena_id = $res['gId'];
                $gameArena = GameArena::where('id', $res['gId'])
                    ->lockForUpdate()
                    ->first();
                if (!$gameArena) {
                    throw new \Exception('对战不存在', -1);
                }
                if ($gameArena->status != 0) {
                    throw new \Exception('对战已开始或已结束', -1);
                }
                $gameIsUser = GameArenaUser::where('game_arena_id', $gameArena->id)->where('user_id', $res['userId'])->first();
                if ($gameIsUser) {
                    throw new \Exception('您已加入本场对战无需重复加入', -1);
                }
                $gameUserCount = GameArenaUser::where('game_arena_id', $gameArena->id)->count('id');
                if ($gameUserCount >= $gameArena->user_num) {
                    throw new \Exception('座位已满', -1);
                }
                $gameUserSeat = GameArenaUser::where(['game_arena_id' => $gameArena->id, 'seat' => $res['seatNum']])->first();
                if ($gameUserSeat) {
                    throw new \Exception('座位已被其他玩家抢占', -1);
                }
                $user = User::find($res['userId']);
                //增加亏损
                $user->increment('loss', $gameArena->total_bean);
                //排行统计
                GameRanking::write($res['userId'], $gameArena->total_bean);
                //写记录
                BeanChangeRecord::add(0,2,-$gameArena->total_bean,$res['userId']);
                //写入用户列表
                $gameArenaUser = new GameArenaUser();
                $gameArenaUser->game_arena_id = $gameArena->id;
                $gameArenaUser->user_id = $res['userId'];
                $gameArenaUser->seat = $res['seatNum'];
                $gameArenaUser->save();

                BoxService::open($gameArena->id, $res['userId'], $gameArenaUser);
                WebSocketMsgPushService::pushMsg('joinGroup', [
                    'id' => $gameArenaUser->id,
                    'game_arena_id' => $gameArena->id,
                    'user_id' => $user->id,
                    'seat' => $gameArenaUser->seat,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                ]);

                $gameUser = GameArenaUser::where('game_arena_id', $gameArena->id)->orderByDesc('worth')->get();
                if (count($gameUser) == $gameArena->user_num) {
                    //状态设为进行中
                    $gameArena->status = 1;
                    $gameArena->save();
                    //推送进行中消息
                    //$this->pushMsg('startGroup', ['game_arena_id' => $gameArena->id, 'status' => 1, 'status_alias' => '进行中']);
                    WebSocketMsgPushService::pushMsg('startGroup', ['game_arena_id' => $gameArena->id, 'status' => 1, 'status_alias' => '进行中']);
                    //取最大值奖励价值
                    $win_user_worth = $gameUser[0]['worth'];
                    //赢家参与用户表ID
                    $win_arena_user_id = [];
                    //赢家用户ID
                    $win_user_id = [];
                    //输家参与用户表ID
                    $loser_arena_user_id = [];
                    //输家用户ID
                    $loser_user_id = [];
                    //赢家价值
                    $win_worth = [];
                    $win_num = 0;
                    foreach ($gameUser as $item) {
                        $gameAward = GameAwardRecord::where('game_arena_id', $gameArena->id)
                            ->where('user_id', $item->user_id)->get();
                        if ($win_user_worth == $item->worth) {
                            $win_num++;
                            $win_user_id[] = $item->user_id;
                            $win_arena_user_id[] = $item->id;
                            $win_worth[$item->id] = 0;
                            //设为赢家
                            $item->is_win = 1;
                            $item->save();
                            $t1 = 0;
                            foreach ($gameAward as $award) {

                                $skins = Skins::find($award->award_id);
                                //累加赢家价值
                                $win_worth[$item->id] += $award->award_bean;
                                //获取等级
                                $contain = BoxContain::select('level')->where(['box_id' => $award->box_id,'skin_id' => $skins->id])->first();
                                if ($contain){
                                    $level = $contain->level;
                                } else {
                                    $level = 0;
                                }
                                //写开箱记录
                                $box_record = new BoxRecord();
                                //生成UUID
                                $box_record->get_user_id = $item->user_id;
                                $box_record->user_id = $item->user_id;
                                $box_record->box_id = $award->box_id;
                                $box_record->box_name = $award->box->name;
                                $box_record->box_bean = $award->box_bean;
                                $box_record->skin_id = $skins->id;
                                $box_record->name = $skins->name;
                                $box_record->cover = $skins->getRawOriginal('cover');
                                $box_record->dura = $skins->dura;
                                $box_record->bean = $award->award_bean;
                                $box_record->code = getUniqueOrderNumber();
                                $box_record->lv = $level;
                                $box_record->type = 3;
                                $box_record->is_purse = $skins->is_purse;
                                $box_record->save();
                                TopHistory::dispatch([$box_record->id])->delay(now()->addSeconds($t1));
                                $t1 += 10;
                            }
                        } else {
                            $loser_arena_user_id[] = $item->id;
                            $loser_user_id[] = $item->user_id;
                        }
                    }
                    if (count($win_user_id) > 1) {
                        $totalWorth = GameArenaUser::whereIn('id', $loser_arena_user_id)->sum('worth');
                        $oneBean = bcdiv($totalWorth, count($win_user_id), 2);
                        //增加赢家货币
                        User::whereIn('id', $win_user_id)->increment('bean', $oneBean);
                        //写收支明细//
                        if ($oneBean > 0){
                            foreach ($win_user_id as $userId){
                                BeanChangeRecord::add(1,9,$oneBean,$userId);
                            }
                        }
                        //新增记录
                        foreach ($loser_user_id as $loserUserId){
                            $gameAward = GameAwardRecord::where('game_arena_id', $gameArena->id)
                                ->where('user_id', $loserUserId)
                                ->get();
                            $t2 = 0;
                            foreach ($gameAward as $award) {
                                $skins = Skins::find($award->award_id);
                                //获取等级
                                $contain = BoxContain::select('level')->where(['box_id' => $award->box_id,'skin_id' => $skins->id])->first();
                                if ($contain){
                                    $level = $contain->level;
                                } else {
                                    $level = 0;
                                }
                                //累加赢家价值
                                $win_worth[$win_arena_user_id[0]] += $award->award_bean;

                                $box_record = new BoxRecord();
                                $box_record->get_user_id = $award->user_id;
                                $box_record->user_id = $win_user_id[0];
                                $box_record->box_id = $award->box_id;
                                $box_record->box_name = $award->box->name;
                                $box_record->box_bean = $award->box_bean;
                                $box_record->skin_id = $skins->id;
                                $box_record->name = $skins->name;
                                $box_record->cover = $skins->getRawOriginal('cover');
                                $box_record->dura = $skins->dura;
                                $box_record->bean = $award->award_bean;
                                $box_record->code = getUniqueOrderNumber();
                                $box_record->lv = $level;
                                $box_record->type = 3;
                                $box_record->is_purse = $skins->is_purse;
                                $box_record->status = 2;
                                $box_record->save();
                                TopHistory::dispatch([$box_record->id])->delay(now()->addSeconds($t2));
                                $t2 += 10;
                            }
                        }
                    } else {
                        foreach ($loser_user_id as $loserUserId){
                            $gameAward = GameAwardRecord::where('game_arena_id', $gameArena->id)
                                ->where('user_id', $loserUserId)
                                ->get();
                            $t2 = 0;
                            foreach ($gameAward as $award) {
                                $skins = Skins::find($award->award_id);
                                //获取等级
                                $contain = BoxContain::select('level')->where(['box_id' => $award->box_id,'skin_id' => $skins->id])->first();
                                if ($contain){
                                    $level = $contain->level;
                                } else {
                                    $level = 0;
                                }
                                //累加赢家价值
                                $win_worth[$win_arena_user_id[0]] += $award->award_bean;

                                $box_record = new BoxRecord();
                                $box_record->get_user_id = $award->user_id;
                                $box_record->user_id = $win_user_id[0];
                                $box_record->box_id = $award->box_id;
                                $box_record->box_name = $award->box->name;
                                $box_record->box_bean = $award->box_bean;
                                $box_record->skin_id = $skins->id;
                                $box_record->name = $skins->name;
                                $box_record->cover = $skins->getRawOriginal('cover');
                                $box_record->dura = $skins->dura;
                                $box_record->bean = $award->award_bean;
                                $box_record->code = getUniqueOrderNumber();
                                $box_record->lv = $level;
                                $box_record->type = 3;
                                $box_record->is_purse = $skins->is_purse;
                                $box_record->save();
                                TopHistory::dispatch([$box_record->id])->delay(now()->addSeconds($t2));
                                $t2 += 10;
                            }
                        }
                    }
                    //写入赢家价值
                    if (count($win_user_id) > 1) {
                        foreach ($win_worth as $id => $winWorth) {
                            $winWorth += $oneBean;
                            //写赢家价值
                            GameArenaUser::where('id', $id)->increment('win_worth', $winWorth);
                        }
                        //减去亏损
                        User::whereIn('id', $win_user_id)->decrement('loss', $winWorth);
                    } else {
                        foreach ($win_worth as $id => $winWorth) {
                            GameArenaUser::where('id', $id)->increment('win_worth', $winWorth);
                        }
                        //减去亏损
                        User::whereIn('id', $win_user_id)->decrement('loss', $winWorth);
                    }
                    //写入输家
                    GameArenaUser::whereIn('id', $loser_arena_user_id)->increment('win_worth', 0.01);
                    //输家安慰奖0.01
                    User::whereIn('id', $loser_user_id)->increment('bean', 0.01);
                    //这里可以写收支记录//
                    foreach ($loser_user_id as $userId){
                        BeanChangeRecord::add(1,10,0.01,$userId);
                    }
                    //减去亏损
                    User::whereIn('id', $loser_user_id)->decrement('loss', 0.01);

                    $gameArena->win_user_id = $win_user_id;
                    $gameArena->draw_code = getGameDrawCode();
                    $gameArena->status = 2;
                    $gameArena->save();
                    //更新开箱显示状态
                    GameAwardRecord::where('game_arena_id', $gameArena->id)->update(['status' => 1]);

                    $data = GameArena::with([
                        'game_arena_box' => function ($query) {
                            return $query->with(['box' => function ($query) {
                                return $query->with(['contains' => function ($query) {
                                    return $query->with(['skins' => function ($query) {
                                        return $query->select(['id', 'name', 'bean', 'cover', 'dura']);
                                    }])->select(['box_contains.id', 'box_contains.box_id', 'box_contains.skin_id','box_contains.level'])
                                        ->join('skins', 'skins.id', '=', 'box_contains.skin_id')
                                        ->where('box_contains.is_game', 1)
                                        ->orderBy('skins.bean', 'desc');
                                }])->select(['id', 'name', 'game_bean', 'intact_cover']);
                            }])->select(['id', 'game_arena_id', 'box_id']);
                        },
                        'game_arena_player' => function ($query) use ($game_arena_id) {
                            return $query->with([
                                'game_award' => function ($query) use ($game_arena_id) {
                                    $query->with(['box:id,name,intact_cover,game_bean',
                                        'skins' => function ($query) {
                                            return $query->select(['id', 'name', 'cover', 'dura', 'bean']);
                                        }])->select(['id', 'game_arena_id', 'user_id', 'box_id', 'award_id', 'status'])
                                        ->where('game_arena_id', $game_arena_id)->where('status', 1);
                                },
                                'user:id,name,avatar'
                            ])->select(['id', 'game_arena_id', 'user_id', 'seat']);
                        }])
                        ->where('id', $game_arena_id)
                        ->first()
                        ->toArray();
                    WebSocketMsgPushService::pushMsg('endGroup', $data);
                }
            });
        } catch (\Exception $e) {
            if ($e->getCode() != -1) {
                //记录错误
                \Log::error('加入对战失败', [
                    'Message' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                ]);
            }
        }
    }
}
