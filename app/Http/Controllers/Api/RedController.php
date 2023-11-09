<?php


namespace App\Http\Controllers\Api;

use App\Red;
use App\User;
use App\RedKey;
use App\RedRecord;
use App\BeanRecord;
use App\BeanChangeRecord;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RedController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['index']]);
    }

    /**
     * 红包活动首页
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = Red::select(['id', 'title', 'briefe', 'min_recharge', 'num', 'start_time', 'end_time'])
            ->where('end_time', '>=', now())
            ->get()
            ->toArray();
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 打开红包
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function open()
    {
        $rules = [
            'type' => ['required', 'integer', 'in:1,2'],
        ];
        if (request()->post('type') == 1) {
            $rules['id'] = ['required', 'integer'];
        } else {
            $rules['key'] = ['required', 'string', 'size:32'];
        }
        $validator = Validator::make(request()->post(), $rules, [
            'type.required' => '红包类型错误',
            'type.integer' => '红包类型错误',
            'type.in' => '红包类型错误',
            'id.required' => '红包编号错误',
            'id.integer' => '红包编号错误',
            'key.required' => '红包口令错误',
            'key.string' => '红包口令错误',
            'key.size' => '红包口令错误'
        ]);

        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }

        $bean = 0;
        if (request()->post('type') == 1) {
            //红包活动
            try {
                //数据库事务处理
                DB::transaction(function () use (&$bean) {
                    $user_id = auth('api')->id();
                    $red_id = request()->post('id');

                    $red = Red::find($red_id);
                    if (!$red_id) {
                        throw new \Exception('红包编号错误', -1);
                    }
                    if ($red->start_time > now()) {
                        throw new \Exception('红包还未开抢', -1);
                    }
                    if ($red->end_time < now()) {
                        throw new \Exception('红包已结束', -1);
                    }
                    $red_record_count = RedRecord::where(['red_id' => $red_id, 'type' => 1])->count('id');
                    if ($red->num <= $red_record_count) {
                        throw new \Exception('红包已被抢完啦~', -1);
                    }
                    $user_total_bean = BeanRecord::where(['user_id' => $user_id, 'status' => 1])->where('created_at', '>=', $red->start_time)->sum('bean');
                    if ($red->min_recharge > 0 && $user_total_bean < $red->min_recharge) {
                        throw new \Exception('无法打开红包,红包要求自 ' . $red->start_time . ' 起充值金额达到 ' . $red->min_recharge . getConfig('bean_name') . ' 才可开启！', -1);
                    }
                    $is_red_user_record = RedRecord::query()->where(['red_id' => $red_id, 'user_id' => $user_id])
                        //->where( 'created_at','>',date('Y-m-d'))
                        //->where( 'created_at','<',date('Y-m-d',strtotime('+1 day')))
                        ->exists();
                    if ($is_red_user_record) {
                        throw new \Exception('您已开启过该红包！', -1);
                    }
                    //计算获得金豆
                    $percentage = $red->percentage;
                    $bean = randFloat($percentage[0], $percentage[1]);
                    $bean = number_format($bean, 2);
                    //用户增加金豆
                    $user = User::where('id', $user_id)->lockForUpdate()->first();
                    //金豆入账
                    $user->increment('bean', $bean);
                    //写收支记录
                    BeanChangeRecord::add(1, 11, $bean);
                    //减去战损
                    User::where('id', $user_id)->decrement('loss', $bean);
                    //写记录
                    $red_record = new RedRecord();
                    $red_record->red_id = $red_id;
                    $red_record->user_id = $user_id;
                    $red_record->bean = $bean;
                    $red_record->type = 1;
                    $red_record->save();
                });
            } catch (\Exception $e) {
                $message = '系统错误 开启红包失败';
                if ($e->getCode() == -1) {
                    $message = $e->getMessage();
                }
                return self::apiJson(500, $message);
            }
        } else {
            //口令红包
            try {
                //数据库事务处理
                DB::transaction(function () use (&$bean) {
                    $user_id = auth('api')->id();
                    $user = User::where('id', $user_id)->lockForUpdate()->first();
                    $code = request()->post('key');
                    $red_key = RedKey::where(['code' => $code])->lockForUpdate()->first();
                    if (!$red_key || $red_key->status != 1) {
                        throw new \Exception('红包口令错误！', -1);
                    } elseif ($red_key->threshold > 0 && $user->total_recharge < $red_key->threshold) {
                        throw new \Exception('您未满足打开该口令红包的条件，要求累计充值达到 ' . $red_key->threshold . getConfig('bean_name') . ' 才可使用该口令。', -1);
                    }
                    $red_record = RedRecord::where(['user_id' => $user_id, 'red_key' => $code])->exists();
                    if ($red_record) {
                        throw new \Exception('您已领取过该口令红包，无法重复领取！', -1);
                    } elseif ($red_key->quantity_used >= $red_key->quantity) {
                        throw new \Exception('该红包口令已过期或已用尽！', -1);
                    }

                    //计算获得金豆
                    $percentage = $red_key->denomination;
                    $bean = randFloat($percentage[0], $percentage[1]);
                    $bean = number_format($bean, 2);

                    //金豆入账
                    $user->increment('bean', $bean);
                    //增加使用次数
                    $red_key->increment('quantity_used');
                    //写收支记录
                    BeanChangeRecord::add(1, 11, $bean);
                    //减去战损
                    User::where('id', $user_id)->decrement('loss', $bean);
                    //写记录
                    $red_record = new RedRecord();
                    $red_record->user_id = $user_id;
                    $red_record->red_key = $red_key->code;
                    $red_record->bean = $bean;
                    $red_record->type = 2;
                    $red_record->save();
                });
            } catch (\Exception $e) {
                $message = '开启红包失败';
                if ($e->getCode() == -1) {
                    $message = $e->getMessage();
                }
                return self::apiJson(500, $message);
            }
        }
        return self::apiJson(200, 'ok', $bean);
    }
}
