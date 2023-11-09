<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\SignIn;
use App\UserRewardLog;
use App\BeanChangeRecord;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => []]);
    }

    /**
     * 任务进度
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user_id = auth('api')->id();
        $date = date('Y-m-d');

        //签到天数
        $dateTime = strtotime(date('Y-m-d'));
        $beforeDate = $dateTime - 86400;
        $model = SignIn::where('user_id', $user_id)
            ->first();
        $days = 0;
        if ($model && ($model->days === 7 || $model->sign_time >= $beforeDate)) {
            $days = $model->days;
        }
        //当日开箱消耗金币
        $todayBoxBean = BeanChangeRecord::where(['user_id' => $user_id, 'change_type' => 1])
            ->where('created_at', 'LIKE', $date . '%')
            ->sum('bean');
        //今日对战消耗
        $todayGameBean = BeanChangeRecord::where(['user_id' => $user_id, 'change_type' => 2])
            ->where('created_at', 'LIKE', $date . '%')
            ->sum('bean');
        //今日拉货消耗
        $todayLuckyBean = BeanChangeRecord::where(['user_id' => $user_id, 'change_type' => 3])
            ->where('created_at', 'LIKE', $date . '%')
            ->sum('bean');
        return self::apiJson(200, 'ok', [
            'signDays' => [
                'days' => $days,
                'is_receive' => $days == 7 ? 1 : 0
            ],
            'todayBoxBean200' => [
                'progress' => abs($todayBoxBean),
                'is_receive' => abs($todayBoxBean) < 200 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 9])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayBoxBean1000' => [
                'progress' => abs($todayBoxBean),
                'is_receive' => abs($todayBoxBean) < 1000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 10])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayBoxBean5000' => [
                'progress' => abs($todayBoxBean),
                'is_receive' => abs($todayBoxBean) < 5000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 11])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayBoxBean10000' => [
                'progress' => abs($todayBoxBean),
                'is_receive' => abs($todayBoxBean) < 10000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 12])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayGameBean200' => [
                'progress' => abs($todayGameBean),
                'is_receive' => abs($todayGameBean) < 200 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 13])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayGameBean1000' => [
                'progress' => abs($todayGameBean),
                'is_receive' => abs($todayGameBean) < 1000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 14])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayGameBean5000' => [
                'progress' => abs($todayGameBean),
                'is_receive' => abs($todayGameBean) < 5000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 15])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayGameBean10000' => [
                'progress' => abs($todayGameBean),
                'is_receive' => abs($todayGameBean) < 10000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 16])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayLuckyBean200' => [
                'progress' => abs($todayLuckyBean),
                'is_receive' => abs($todayLuckyBean) < 200 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 17])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayLuckyBean1000' => [
                'progress' => abs($todayLuckyBean),
                'is_receive' => abs($todayLuckyBean) < 1000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 18])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayLuckyBean2000' => [
                'progress' => abs($todayLuckyBean),
                'is_receive' => abs($todayLuckyBean) < 2000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 19])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayLuckyBean5000' => [
                'progress' => abs($todayLuckyBean),
                'is_receive' => abs($todayLuckyBean) < 5000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 20])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],
            'todayLuckyBean10000' => [
                'progress' => abs($todayLuckyBean),
                'is_receive' => abs($todayLuckyBean) < 10000 ? 0 : (UserRewardLog::where(['user_id' => $user_id, 'type' => 20])->where('created_at', 'LIKE', $date . '%')->exists() ? 2 : 1)
            ],


        ]);
    }

    /**
     * 签到
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function signUp()
    {
        \DB::beginTransaction();
        try {
            $user_id = auth('api')->id();
            $date = strtotime(date('Y-m-d'));
            $beforeDate = $date - 86400;
            $model = SignIn::where('user_id', $user_id)
                ->first();

            if ($model && $model->days === 7) {
                throw new \Exception('您已连续签到7天，请先领取签到奖励！', -1);
            } elseif ($model && $model->sign_time === $date) {
                throw new \Exception('您今天已经签过到了~~~', -1);
            }

            if ($model) {
                if ($model->sign_time >= $beforeDate) {
                    $model->days = $model->days + 1;
                    $model->sign_time = $date;
                    $model->save();
                } else {
                    $model->days = 1;
                    $model->sign_time = $date;
                    $model->save();
                }
            } else {
                $model = new SignIn();
                $model->user_id = $user_id;
                $model->days = 1;
                $model->sign_time = $date;
                $model->save();
            }
            \DB::commit();
            return self::apiJson(200, '签到成功！');
        } catch (\Exception $e) {
            \DB::rollBack();
            return self::apiJson(500, $e->getMessage());
        }
    }

    /**
     * 领取任务奖励
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function receive()
    {
        $validator = Validator::make(request()->post(), [
            'type' => 'required|integer|in:1,2,3,4,5,6,7,8,9,10,11,12,13'
        ], [
            'type.required' => '缺少奖励类型',
            'type.integer' => '奖励类型错误',
            'type.in' => '奖励类型错误'
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }

        $type = request()->post('type');
        $user_id = auth('api')->id();
        $date = date('Y-m-d');
        $w = date('w', strtotime($date));
        $week_start = date('Y-m-d', strtotime("$date -" . ($w ? $w - 1 : 6) . ' days'));
        if ($type == 1) {
            //签到奖励
            \DB::beginTransaction();
            try {
                $user = User::where('id', $user_id)->lockForUpdate()->first();
                $model = SignIn::where('user_id', $user_id)->first();
                if (!$model || $model->days < 7) {
                    throw new \Exception('领取失败，连续签到未满7天！');
                }
                $bean = number_format(randFloat(8, 18), 2);
                //入账
                $user->increment('bean', $bean);
                //写收支明细
                BeanChangeRecord::add(1, 18, $bean);
                //写奖励记录
                $log = new UserRewardLog();
                $log->user_id = $user_id;
                $log->type = 8;
                $log->next_user_id = null;
                $log->bean = $bean;
                $log->save();
                //重置签到任务
                $model->days = 0;
                $model->save();
                \DB::commit();
                return self::apiJson(200, 'ok', [
                    'amount' => $bean
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                return self::apiJson(500, $e->getMessage());
            }
        } elseif (in_array($type, [2, 3, 4, 5])) {
            //当日开箱奖励
            \DB::beginTransaction();
            try {
                $user = User::where('id', $user_id)->lockForUpdate()->first();
                $todayBoxBean = BeanChangeRecord::where(['user_id' => $user_id, 'change_type' => 1])
                    ->where('created_at', 'LIKE', $date . '%')
                    ->sum('bean');
                $todayBoxBean = abs($todayBoxBean);

                $coins = [
                    2 => 100,
                    3 => 300,
                    4 => 1000,
                    5 => 5000
                ];
                $minBean = $coins[$type];
                if ($todayBoxBean < $minBean) {
                    throw new \Exception('您今日经典盲盒花费未满 ' . $minBean . ' T币！');
                }
                $types = [
                    2 => 9,
                    3 => 10,
                    4 => 11,
                    5 => 12
                ];
                $typeId = $types[$type];
                if (UserRewardLog::where(['user_id' => $user_id, 'type' => $typeId])->where('created_at', 'LIKE', $date . '%')->exists()) {
                    throw new \Exception('您今日已领取过该奖励！');
                }
                $beanList = [
                    2 => [1, 6],
                    3 => [3, 15],
                    4 => [10, 50],
                    5 => [50, 110],
                ];
                $beans = $beanList[$type];
                $bean = number_format(randFloat($beans[0], $beans[1]), 2);
                //入账
                $user->increment('bean', $bean);
                //写收支明细
                $changeTypes = [
                    2 => 19,
                    3 => 20,
                    4 => 21,
                    5 => 22,
                ];
                $changeType = $changeTypes[$type];
                BeanChangeRecord::add(1, $changeType, $bean);
                //写奖励记录
                $log = new UserRewardLog();
                $log->user_id = $user_id;
                $log->type = $typeId;
                $log->next_user_id = null;
                $log->bean = $bean;
                $log->save();
                \DB::commit();
                return self::apiJson(200, 'ok', [
                    'amount' => $bean
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                return self::apiJson(500, $e->getMessage());
            }
        } elseif (in_array($type, [6, 7, 8, 9])) {
            //当日对战奖励
            \DB::beginTransaction();
            try {
                $user = User::where('id', $user_id)->lockForUpdate()->first();
                $todayGameBean = BeanChangeRecord::query()->where(['user_id' => $user_id, 'change_type' => 2])
                    ->where('created_at', 'LIKE', $date . '%')
                    ->sum('bean');
                $todayGameBean = abs($todayGameBean);
                $coins = [
                    6 => 100,
                    7 => 300,
                    8 => 1000,
                    9 => 5000
                ];
                $minBean = $coins[$type];
                if ($todayGameBean < $minBean) {
                    throw new \Exception('您今日盲盒对战花费未满 ' . $minBean . ' T币！');
                }
                $types = [
                    6 => 13,
                    7 => 14,
                    8 => 15,
                    9 => 16
                ];
                $typeId = $types[$type];
                if (UserRewardLog::where(['user_id' => $user_id, 'type' => $typeId])->where('created_at', 'LIKE', $date . '%')->exists()) {
                    throw new \Exception('您今日已领取过该奖励！');
                }
                $beanList = [
                    6 => [1, 6],
                    7 => [3, 15],
                    8 => [10, 50],
                    9 => [50, 110]
                ];
                $beans = $beanList[$type];
                $bean = number_format(randFloat($beans[0], $beans[1]), 2);
                //入账
                $user->increment('bean', $bean);
                //写收支明细
                $changeTypes = [
                    6 => 24,
                    7 => 25,
                    8 => 26,
                    9 => 27,
                ];
                $changeType = $changeTypes[$type];
                BeanChangeRecord::add(1, $changeType, $bean);
                //写奖励记录
                $log = new UserRewardLog();
                $log->user_id = $user_id;
                $log->type = $typeId;
                $log->next_user_id = null;
                $log->bean = $bean;
                $log->save();
                \DB::commit();
                return self::apiJson(200, 'ok', [
                    'amount' => $bean
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                return self::apiJson(500, $e->getMessage());
            }
        } elseif (in_array($type, [10, 11, 12, 13])) {
            //当日追梦任务
            \DB::beginTransaction();
            try {
                $user = User::where('id', $user_id)->lockForUpdate()->first();
                $todayLuckyBean = BeanChangeRecord::where(['user_id' => $user_id, 'change_type' => 3])
                    ->where('created_at', 'LIKE', $date . '%')
                    ->sum('bean');
                $todayLuckyBean = abs($todayLuckyBean);
                $coins = [
                    10 => 100,
                    11 => 300,
                    12 => 1000,
                    13 => 5000
                ];
                $minBean = $coins[$type];
                if ($todayLuckyBean < $minBean) {
                    throw new \Exception('您今日追梦花费未满 ' . $minBean . ' T币！');
                }
                $types = [
                    10 => 17,
                    11 => 18,
                    12 => 19,
                    13 => 20
                ];
                $typeId = $types[$type];
                if (UserRewardLog::where(['user_id' => $user_id, 'type' => $typeId])->where('created_at', 'LIKE', $date . '%')->exists()) {
                    throw new \Exception('您今日已领取过该奖励！');
                }
                $beanList = [
                    10 => [1, 6],
                    11 => [3, 15],
                    12 => [10, 50],
                    13 => [50, 110]
                ];
                $beans = $beanList[$type];
                $bean = number_format(randFloat($beans[0], $beans[1]), 2);
                //入账
                $user->increment('bean', $bean);
                //写收支明细
                $changeTypes = [
                    10 => 28,
                    11 => 29,
                    12 => 30,
                    13 => 31
                ];
                $changeType = $changeTypes[$type];
                BeanChangeRecord::add(1, $changeType, $bean);
                //写奖励记录
                $log = new UserRewardLog();
                $log->user_id = $user_id;
                $log->type = $typeId;
                $log->next_user_id = null;
                $log->bean = $bean;
                $log->save();
                \DB::commit();
                return self::apiJson(200, 'ok', [
                    'amount' => $bean
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                return self::apiJson(500, $e->getMessage());
            }
        } else {
            return self::apiJson(500, '系统错误');
        }
    }
}
