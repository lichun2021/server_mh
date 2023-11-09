<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/11/6 17:20
 */

namespace App\Http\Controllers\Api;

use App\Box;
use App\BoxContain;
use App\GameWinRanking;
use App\Jobs\TopHistory;
use App\GameRanking;
use App\Skins;
use App\User;
use App\BoxRecord;
use App\GameArena;
use App\GameArenaBox;
use App\GameArenaUser;
use App\BeanChangeRecord;
use App\GameAwardRecord;
use App\Services\BoxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Services\WebSocketMsgPushService;

class GameArenaController extends Controller
{
    /**
     * @var string 对战频道
     */
    private static $group_id = 'csgo_atm_game_arena';

    /**
     * GameArenaController constructor.
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['JoinChannel', 'history', 'list', 'boxList', 'detail', 'ranking', 'WinRanking']]);
        WebSocketMsgPushService::$group = self::$group_id;
    }

    /**
     * 对战列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $data = GameArena::with([
            'game_arena_box' => function ($query) {
                return $query->with(['box:id,name,game_bean,intact_cover'])
                    ->select(['id', 'game_arena_id', 'box_id']);
            },
            'game_arena_player' => function ($query) {
                return $query->with(['user:id,name,avatar'])
                    ->select(['id', 'game_arena_id', 'user_id', 'seat']);
            }])->whereIn('status', [0, 1])
            ->orWhere(function ($query) {
                $query->where('status', 2)
                    ->where('updated_at', '>=', date('Y-m-d H:i:s', time() - 60));
            })
            //->orderBy('bot_id')
            ->orderBy('status')
            ->orderBy('id')
            ->Paginate(12);
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 创建对战
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function create()
    {
        $validator = Validator::make(request()->post(), [
            'user_num' => ['required', 'in:2,3,4'],
            'box' => ['required', 'array', 'min:1', 'max:6']
        ], [
            'user_num.required' => '请选择对战模式',
            'user_num.in' => '对战模式错误',
            'box.required' => '请选择宝箱',
            'box.array' => '宝箱数据不正确',
            'box.min' => '至少选择一个宝箱',
            'box.max' => '最多支持6个宝箱'
        ]);

        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        // } elseif (auth('api')->user()->anchor == 1){
            // return self::apiJson(500, '您的账号对战权限已被关闭。');
        }

        try {
            $data = [];
            DB::transaction(function () use (&$data) {
                $user = User::where('id', auth('api')->id())->lockForUpdate()->first();
                //创建对战
                $gameArena = new GameArena();
                $gameArena->create_user_id = $user->id;
                $gameArena->user_num = request()->post('user_num');
                $gameArena->box_num = count(request()->post('box'));
                $gameArena->save();

                $box_ids = [];
                $total_bean = 0;
                foreach (request()->post('box') as $box_id) {
                    $box = Box::where('id', $box_id)
                        ->where('is_game', 1)
                        ->first();
                    if (!$box) {
                        throw new \Exception("宝箱信息不存在", -1);
                    }
                    $box_ids[] = $box->id;
                    $total_bean += $box->game_bean;
                    $gameArenaBox = new GameArenaBox();
                    $gameArenaBox->game_arena_id = $gameArena->id;
                    $gameArenaBox->box_id = $box->id;
                    $gameArenaBox->box_bean = $box->game_bean;
                    $gameArenaBox->save();
                }
                if ($user->bean < $total_bean) {
                    throw new \Exception(getConfig('bean_name') . '不足！', -1);
                } elseif ($user->is_recharge === 0){
                    throw new \Exception('进行任意额度充值即可开箱提取，为了维护广大用户的合法权益，防止机器人恶意注册,谢谢理解!', -1);
                }
                //减少金豆
                $user->decrement('bean', $total_bean);
                //增加亏损
                $user->increment('loss', $total_bean);
                //写记录
                BeanChangeRecord::add(0, 2, -$total_bean);
                //增加积分
                $user->increment('integral', $total_bean);
                //排行统计
                GameRanking::write($user->id, $total_bean);
                //对战总价值写入;
                $gameArena->total_bean = $total_bean;
                $gameArena->save();
                //写入用户列表
                $gameArenaUser = new GameArenaUser();
                $gameArenaUser->game_arena_id = $gameArena->id;
                $gameArenaUser->user_id = $user->id;
                $gameArenaUser->seat = 0;
                $gameArenaUser->save();
                //查询对战
                $game_arena_id = $gameArena->id;
                $data = GameArena::with([
                    'game_arena_box' => function ($query) {
                        return $query->with(['box:id,name,game_bean,intact_cover'])
                            ->select(['id', 'game_arena_id', 'box_id']);
                    },
                    'game_arena_player' => function ($query) {
                        return $query->with(['user:id,name,avatar'])
                            ->select(['id', 'game_arena_id', 'user_id', 'seat']);
                    }])
                    ->where('id', $game_arena_id)
                    ->first()->toArray();
                //预开箱
                //$this->open($gameArena->id, $user->id, $gameArenaUser);
                BoxService::open($gameArena->id, $user->id, $gameArenaUser);
                //全频道推送
                //$this->pushMsg('CreateGroup', $data);
                WebSocketMsgPushService::pushMsg('CreateGroup', $data);
            });
        } catch (\Exception $e) {
            $message = '对战创建失败！';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            } else {
                //记录错误
                Log::error('创建对战失败', [
                    'Message' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                ]);
            }

            return self::apiJson(500, $message);
        }
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 对战详情
     * @param integer $game_arena_id 对战ID
     * @param string $client_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make(request()->post(), [
            'game_arena_id' => ['required', 'integer', 'min:0'],
        ], [
            'game_arena_id.required' => '缺少对战编号',
            'game_arena_id.integer' => '对战编号错误',
            'game_arena_id.min' => '对战编号错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }
        $game_arena_id = request()->get('game_arena_id');

        $data = GameArena::with([
            'game_arena_box' => function ($query) {
                return $query->with(['box' => function ($query) {
                    return $query->with(['contains' => function ($query) {
                        return $query->with(['skins' => function ($query) {
                            return $query->select(['id', 'name', 'bean', 'cover', 'dura']);
                        }])->select(['box_contains.id', 'box_contains.box_id', 'box_contains.skin_id', 'box_contains.level'])
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
            ->first();

        if (empty($data)) {
            return self::apiJson(500, '对战信息不存在');
        }
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * @param integer $game_arena_id 对战ID
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function join()
    {
        $validator = Validator::make(request()->post(), [
            'game_arena_id' => ['required', 'integer', 'min:0'],
            'seat' => ['required', 'in:1,2,3']
        ], [
            'game_arena_id.required' => '请输入对战ID',
            'game_arena_id.integer' => '对战ID错误',
            'game_arena_id.min' => '对战ID错误',
            'seat.required' => '请输入座位号',
            'seat.in' => '座位号错误'
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        // } elseif (auth('api')->user()->anchor == 1){
        //     return self::apiJson(500, '您的账号对战权限已被关闭。');
        }

        try {
            DB::transaction(function () {
                $user_id = auth('api')->id();
                $seat = request()->post('seat');
                $game_arena_id = request()->post('game_arena_id');
                $gameArena = GameArena::where('id', $game_arena_id)
                    ->lockForUpdate()
                    ->first();
                if (!$gameArena) {
                    throw new \Exception('对战不存在', -1);
                }
                if ($gameArena->status != 0) {
                    throw new \Exception('对战已开始或已结束', -1);
                }
                $gameIsUser = GameArenaUser::where('game_arena_id', $gameArena->id)->where('user_id', $user_id)->first();
                if ($gameIsUser) {
                    throw new \Exception('您已加入本场对战无需重复加入', -1);
                }
                $gameUserCount = GameArenaUser::where('game_arena_id', $gameArena->id)->count('id');
                if ($gameUserCount >= $gameArena->user_num) {
                    throw new \Exception('座位已满', -1);
                }
                if (($seat + 1) > $gameArena->user_num) {
                    throw new \Exception('座位号错误', -1);
                }
                $gameUserSeat = GameArenaUser::query()->where(['game_arena_id' => $gameArena->id, 'seat' => $seat])->first();
                if ($gameUserSeat) {
                    throw new \Exception('座位已被其他玩家抢占', -1);
                }
                $user = User::where('id', $user_id)->lockForUpdate()->first();
                if ($user->bean < $gameArena->total_bean) {
                    throw new \Exception(getConfig('bean_name') . '不足', -1);
                } elseif ($user->is_recharge === 0){
                    throw new \Exception('进行任意额度充值即可开箱提取，为了维护广大用户的合法权益，防止机器人恶意注册,谢谢理解!', -1);
                }
                //减少金豆
                $user->decrement('bean', $gameArena->total_bean);
                //增加亏损
                $user->increment('loss', $gameArena->total_bean);
                //增加积分
                $user->increment('integral', $gameArena->total_bean);
                //排行统计
                GameRanking::write($user->id, $gameArena->total_bean);
                //写记录
                BeanChangeRecord::add(0, 2, -$gameArena->total_bean);
                //写入用户列表
                $gameArenaUser = new GameArenaUser();
                $gameArenaUser->game_arena_id = $gameArena->id;
                $gameArenaUser->user_id = $user->id;
                $gameArenaUser->seat = $seat;
                $gameArenaUser->save();

                BoxService::open($gameArena->id, $user->id, $gameArenaUser);
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
                                $contain = BoxContain::select('level')->where(['box_id' => $award->box_id, 'skin_id' => $skins->id])->first();
                                if ($contain) {
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

                        $oneBean = 0;
                        if ($totalWorth > 0) {
                            $oneBean = bcdiv($totalWorth, count($win_user_id), 2);
                            //增加赢家货币
                            User::whereIn('id', $win_user_id)->increment('bean', $oneBean);
                            //写收支明细//
                            foreach ($win_user_id as $userId) {
                                BeanChangeRecord::add(1, 9, $oneBean, $userId);
                            }
                        }

                        //新增记录
                        foreach ($loser_user_id as $loserUserId) {
                            $gameAward = GameAwardRecord::where('game_arena_id', $gameArena->id)
                                ->where('user_id', $loserUserId)
                                ->get();
                            $t2 = 0;
                            foreach ($gameAward as $award) {
                                $skins = Skins::find($award->award_id);
                                //获取等级
                                $contain = BoxContain::select('level')->where(['box_id' => $award->box_id, 'skin_id' => $skins->id])->first();
                                if ($contain) {
                                    $level = $contain->level;
                                } else {
                                    $level = 0;
                                }
                                //累加赢家价值
                                //$win_worth[$win_arena_user_id[0]] += $award->award_bean;

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
                        foreach ($loser_user_id as $loserUserId) {
                            $gameAward = GameAwardRecord::where('game_arena_id', $gameArena->id)
                                ->where('user_id', $loserUserId)
                                ->get();
                            $t2 = 0;
                            foreach ($gameAward as $award) {
                                $skins = Skins::find($award->award_id);
                                //获取等级
                                $contain = BoxContain::select('level')->where(['box_id' => $award->box_id, 'skin_id' => $skins->id])->first();
                                if ($contain) {
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
                    //欧皇排行统计
                    //GameWinRanking::write($win_user_id, $oneBean);
                    if (count($win_user_id) > 1) {
                        foreach ($win_worth as $id => $winWorth) {
                            $winWorth += $oneBean;
                            //写赢家价值
                            GameArenaUser::where('id', $id)->increment('win_worth', $winWorth);
                        }
                        //减去亏损
                        User::whereIn('id', $win_user_id)->decrement('loss', $winWorth);
                        //欧皇排行统计
                        GameWinRanking::write($win_user_id, $winWorth);
                    } else {
                        foreach ($win_worth as $id => $winWorth) {
                            GameArenaUser::where('id', $id)->increment('win_worth', $winWorth);
                        }
                        //减去亏损
                        User::whereIn('id', $win_user_id)->decrement('loss', $winWorth);
                        //欧皇排行统计
                        GameWinRanking::write($win_user_id, $winWorth);
                    }
                    //写入输家
                    GameArenaUser::whereIn('id', $loser_arena_user_id)->increment('win_worth', 0.01);
                    //输家安慰奖0.01
                    User::whereIn('id', $loser_user_id)->increment('bean', 0.01);
                    //这里可以写收支记录//
                    foreach ($loser_user_id as $userId) {
                        BeanChangeRecord::add(1, 10, 0.01, $userId);
                    }
                    //减去亏损
                    User::whereIn('id', $loser_user_id)->decrement('loss', 0.01);
                    //欧皇排行统计
                    GameWinRanking::write($loser_user_id, 0.01);

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
                                    }])->select(['box_contains.id', 'box_contains.box_id', 'box_contains.skin_id', 'box_contains.level'])
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
                    //$this->pushMsg('endGroup', $data);
                    WebSocketMsgPushService::pushMsg('endGroup', $data);
                }
            });
        } catch (\Exception $e) {
            $message = '加入失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            } else {
                //记录错误
                Log::error('加入对战失败', [
                    'Message' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                ]);
            }
            return self::apiJson(500, $message);
        }
        return self::apiJson(200, '加入成功');
    }

    /**
     * 对战宝箱列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function boxList()
    {
        $key = Box::$fields['cacheKey'][9];
        $data = Cache::get($key);
        if ($data === null) {
            $data = Box::query()->select(['id', 'name', 'intact_cover', 'game_bean'])
                ->with(['contains' => function ($query) {
                    return $query->with(['skins' => function ($query) {
                        return $query->select(['id', 'name', 'bean', 'cover', 'dura']);
                    }])->select(['box_contains.id', 'box_contains.box_id', 'box_contains.skin_id', 'box_contains.level', 'box_contains.odds'])
                        ->join('skins', 'skins.id', '=', 'box_contains.skin_id')
                        ->where('box_contains.is_game', 1)
                        ->orderBy('skins.bean', 'desc');
                }])
                ->where('is_game', 1)
                ->orderBy('sort')
                ->get();
            $data->append(['game_odds_list']);
            foreach ($data as $item) {
                $item->contains->append(['odds_percent', 'level_image_url']);
            }
            $data = $data->toArray();
            Cache::put($key, $data, 60);
        }
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 历史记录
     * @return \Illuminate\Http\JsonResponse
     */
    public function history()
    {
        $data = GameArena::with([
            'game_arena_box' => function ($query) {
                return $query->with(['box:id,name,game_bean,intact_cover'])
                    ->select(['id', 'game_arena_id', 'box_id']);
            },
            'game_arena_player' => function ($query) {
                return $query->with(['user:id,name,avatar'])
                    ->select(['id', 'game_arena_id', 'user_id', 'seat']);
            }])->where('status', 2)
            ->orderByDesc('draw_code')
            ->Paginate(20);
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 我的参与记录
     * @return \Illuminate\Http\JsonResponse
     */
    public function meHistory()
    {
        $data = GameArena::with([
            'game_arena_box' => function ($query) {
                return $query->with(['box:id,name,game_bean,intact_cover'])
                    ->select(['id', 'game_arena_id', 'box_id']);
            },
            'game_arena_player' => function ($query) {
                return $query->with(['user:id,name,avatar'])
                    ->select(['id', 'game_arena_id', 'user_id', 'seat']);
            }])->whereHas('game_arena_player', function (Builder $query) {
            $query->where('user_id', '=', auth('api')->id());
        })
            ->where('status', 2)
            ->orderByDesc('draw_code')
            ->Paginate(20);
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 把WS客户端加入频道中
     * @param string $client_id 客户端ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function JoinChannel()
    {
        $validator = Validator::make(request()->post(), [
            'client_id' => 'required|size:32'
        ], [
            'client_id.required' => '客户端ID不能为空',
            'client_id.size' => '客户端ID错误'
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }
        $client_id = request()->post('client_id');
        if (WebSocketMsgPushService::joinGroup($client_id, self::$group_id)) {
            return self::apiJson(200, '操作成功！');
        }
        return self::apiJson(500, '操作失败，客户端不在线!');
    }

    /**
     * 对战排行
     * @return \Illuminate\Http\JsonResponse
     */
    public function ranking()
    {
        $yesterday = GameRanking::select(['user_id', 'expend'])->with('user:id,name,avatar,vip_level')->where('date', date('Y-m-d', strtotime('-1 day')))
            ->orderByDesc('expend')
            ->orderBy('id')
            ->limit(3)
            ->get()
            ->toArray();
        $today = GameRanking::select(['user_id', 'expend'])->with('user:id,name,avatar,vip_level')->where('date', date('Y-m-d'))
            ->orderByDesc('expend')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->toArray();
        return self::apiJson(200, 'ok', [
            'yesterday' => $yesterday,
            'today' => $today
        ]);
    }

    /**
     * 每日欧皇榜
     * @return \Illuminate\Http\JsonResponse
     */
    public function WinRanking()
    {
        $yesterday = GameWinRanking::select(['user_id', 'win'])->with('user:id,name,avatar,vip_level')->where('date', date('Y-m-d', strtotime('-1 day')))
            ->orderByDesc('win')
            ->orderBy('id')
            ->limit(3)
            ->get()
            ->toArray();
        $today = GameWinRanking::select(['user_id', 'win'])->with('user:id,name,avatar,vip_level')->where('date', date('Y-m-d'))
            ->orderByDesc('win')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->toArray();
        return self::apiJson(200, 'ok', [
            'yesterday' => $yesterday,
            'today' => $today
        ]);
    }
}
