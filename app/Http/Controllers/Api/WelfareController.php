<?php


namespace App\Http\Controllers\Api;


use App\Box;
use App\Skins;
use App\User;
use App\Welfare;
use App\BoxRecord;
use App\BeanRecord;
use App\WelfareCdk;
use App\WelfareRecord;
use App\Jobs\TopHistory;
use Illuminate\Support\Str;
use App\Services\BoxService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class WelfareController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['index']]);
    }

    /**
     * 活动宝箱列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $key = 'welfare_box_list';
        $data = Cache::get($key);

        if ($data === null) {
            $data = Welfare::with(['box' => function ($query) {
                return $query->select(['id', 'name', 'cover', 'bean']);
            }])->select(['id', 'name', 'description', 'box_id', 'type', 'promotion_level'])->orderBy('sort')->get()->toArray();
            Cache::put($key, $data, 30);
        }

        $user = auth('api')->user();
        if ($user !== null) {
            $time = strtotime(date('Y-m-d'));
            foreach ($data as $key => $val) {
                if ($val['type'] == 1) {
                    $beanRecord = BeanRecord::where('user_id', $user->id)->where('status', 1)->where(DB::raw("date_sub(curdate(), INTERVAL 30 DAY)"), '<=', DB::raw("date(`updated_at`)"))->sum('bean');
                    if ($beanRecord) {
                        $record = WelfareRecord::where(['user_id' => $user->id, 'time' => $time])->exists();
                        if ($record) {
                            $data[$key]['remaining_count'] = 0;
                        } else {
                            $data[$key]['remaining_count'] = 1;
                        }
                    } else {
                        $data[$key]['remaining_count'] = 0;
                    }

                } elseif ($val['type'] == 3) {
                    $data[$key]['remaining_count'] = BeanRecord::where(['user_id' => $user->id, 'status' => 1, 'bean' => $val['promotion_level'], 'is_benefit' => 0])->count();
                }
            }
        }

        return response()->json([
            'code' => 200,
            'message' => 'OK',
            'data' => $data
        ]);
    }


    /**
     * 开箱
     *
     * @param int $id 宝箱ID
     * @param string $cdk cdk
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function open()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer|min:1',
            'cdk' => 'string|size:36',
        ], [
            'id.required' => '缺少活动编号Id',
            'id.integer' => '活动编号错误',
            'id.min' => '活动编号错误',
            'cdk.string' => 'CDK错误',
            'cdk.size' => 'CDK错误',

        ]);

        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }
        $list = null;

        $welfare_id = request()->post('id');
        $welfare = Welfare::where('id', $welfare_id)->first();

        if (!$welfare) {
            return self::apiJson(500, '活动编号Id错误!');
        }
        $box_id = $welfare->box_id;

        if (auth('api')->user()->anchor == 1) {
            //Lock Key
            $lockKey = Welfare::$fields['cacheKey'][1] . $box_id;
        } else {
            //Lock Key
            $lockKey = Welfare::$fields['cacheKey'][0] . $box_id;
        }
        //拿到Cache原子锁 最多锁十秒
        $cacheLock = Cache::lock($lockKey, 10);

        try {
            //数据库事务处理
            DB::transaction(function () use (&$list, $cacheLock, $box_id, $welfare) {
                $user = User::where('id', auth('api')->id())->lockForUpdate()->first();
                $box = Box::find($box_id);
                $time = strtotime(date('Y-m-d'));
                if (!$box) {
                    throw new \Exception('宝箱不存在！', -1);
                }

                if ($welfare->type == 1) {
                    $record = WelfareRecord::where(['user_id' => $user->id, 'time' => $time, 'type' => 1])->exists();
                    $beanRecord = BeanRecord::where('user_id', $user->id)->where('status', 1)->where('created_at', '>=', date('Y-m') . '-01')->sum('bean');

                    if ($beanRecord < getConfig('every_day_min_bean')) {
                        throw new \Exception('您本月充值不足 ' . getConfig('every_day_min_bean') . ' ' . getConfig('bean_name') . '，无法开启每日福利！', -1);
                    } elseif ($record) {
                        throw new \Exception("您今天已开启过每日福利宝箱,每天只能开启一次！", -1);
                    } else {
                        $user->new_welfare = 1;
                        $user->save();
                        //写记录
                        $welfareRecord = new WelfareRecord();
                        $welfareRecord->user_id = auth('api')->id();
                        $welfareRecord->type = $welfare->type;
                        $welfareRecord->box_id = $box_id;
                        $welfareRecord->time = $time;
                        $welfareRecord->save();
                    }
                } elseif ($welfare->type == 2) {
                    throw new \Exception('错误的活动编号！', -1);
                    if ($user->total_recharge < $welfare->promotion_level) {
                        throw new \Exception('累计充值额度不足！', -1);
                    }
                    $record = WelfareRecord::where(['user_id' => $user->id, 'time' => $time, 'type' => 2])->exists();
                    if ($record) {
                        throw new \Exception("您今天已开启过累计充值福利宝箱,每天只能开启一次！", -1);
                    }

                    $welfareRecord = new WelfareRecord();
                    $welfareRecord->user_id = $user->id;
                    $welfareRecord->type = $welfare->type;
                    $welfareRecord->box_id = $box_id;
                    $welfareRecord->time = $time;
                    $welfareRecord->other = $welfare->promotion_level;
                    $welfareRecord->save();
                } elseif ($welfare->type == 3) {
                    $bean_record = BeanRecord::where(['user_id' => $user->id, 'status' => 1, 'bean' => $welfare->promotion_level, 'is_benefit' => 0])->first();

                    if (!$bean_record) {
                        throw new \Exception("您没有开启此宝箱的次数！", -1);
                    }
                    //使用
                    $bean_record->is_benefit = 1;
                    $bean_record->save();
                    //写记录
                    $welfareRecord = new WelfareRecord();
                    $welfareRecord->user_id = $user->id;
                    $welfareRecord->type = $welfare->type;
                    $welfareRecord->box_id = $box_id;
                    $welfareRecord->other = $welfare->promotion_level;
                    $welfareRecord->save();
                } elseif ($welfare->type == 4) {
                    $cdk = request()->post('cdk');
                    if (empty($cdk)) {
                        throw new \Exception('请输入CDK兑换码！', -1);
                    }
                    $cdk_model = WelfareCdk::where('key', $cdk)->lockForUpdate()->first();
                    if (!$cdk_model || $cdk_model->welfare_id !== $welfare->id) {
                        throw new \Exception('CDK兑换码错误！', -1);
                    }
                    if ($cdk_model->status != 0) {
                        throw new \Exception('CDK兑换码已使用！', -1);
                    }
                    $cdk_model->user_id = auth('api')->id();
                    $cdk_model->status = 1;
                    $cdk_model->save();
                }

                //5秒内拿不到锁抛出异常
                $cacheLock->block(10);
                //获得饰品Id
                $skins_id = BoxService::getWelfareBoxSkins($box_id);
                //释放锁
                $cacheLock->release();

                $box_award = Skins::find($skins_id);
                //减去战损
                $user->decrement('loss', $box_award->bean);

                $box_record = new BoxRecord();

                $uuid = (string)Str::uuid();

                $box_record->get_user_id = auth('api')->id();
                $box_record->user_id = auth('api')->id();
                $box_record->box_id = $box->id;
                $box_record->box_name = $box->name;
                $box_record->box_bean = 0;
                $box_record->skin_id = $box_award->id;
                $box_record->name = $box_award->name;
                $box_record->cover = $box_award->getRawOriginal('cover');
                $box_record->dura = $box_award->dura;
                $box_record->bean = $box_award->bean;
                $box_record->code = getUniqueOrderNumber();
                $box_record->uuid = $uuid;
                $box_record->type = 2;
                $box_record->is_purse = $box_award->is_purse;
                $box_record->save();
                $list = $box_record;
                TopHistory::dispatch([$box_record->id]);
            });
        } catch (\Exception $e) {
            //解除原子锁
            $cacheLock->release();
            $message = '开箱失败';
            //$message = $e->getMessage().$e->getFile().$e->getLine();

            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            } else {
                \Log::debug('==========免费开箱失败===========', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            $cacheLock->release();
            return self::apiJson(500, $message);
        }

        return self::apiJson(200, 'ok', $list);
    }
}
