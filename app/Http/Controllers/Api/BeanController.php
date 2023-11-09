<?php

namespace App\Http\Controllers\Api;

use App\Bean;
use App\BeanRecord;
use App\BeanChangeRecord;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * 充值相关
 * Class BeanController
 * @package App\Http\Controllers\Api
 * @author 春风 <860646000@qq.com>
 */
class BeanController extends Controller
{
    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['list']]);
    }

    /**
     * 充值列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $key = Bean::$fields['cacheKey'];
        $beans = Cache::get($key);
        if ($beans === null){
            $beans = Bean::select('id', 'price', 'bean','card_link')->where('is_putaway', 1)->get()->toArray();
            Cache::put($key,$beans);
        }
        return self::apiJson(200,'ok',$beans);
    }

    /**
     * 订单详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make(request()->post(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500,$errors);
        }

        $bean_record = BeanRecord::query()->select([
            'user_id',
            'bean',
            'price',
            'code',
            'status',
            'created_at',
            'updated_at'
        ])->where('code', request()->get('code'))->first();

        //var_dump($bean_record);die;
        if (!$bean_record || $bean_record->user_id != auth('api')->id()) {
            return self::apiJson(500,'充值订单号不存在！');
        }

        return self::apiJson(200, 'ok',$bean_record);
    }

    /**
     * 收支明细
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeRecords()
    {
        $validator = Validator::make(request()->query(), [
            'type' => 'integer',
        ],[
            'type.integer' => '类型错误'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        $type = request()->get('type');
        $user_id = auth('api')->id();
        if (empty($type)) {
            $data = BeanChangeRecord::where('user_id', $user_id);
        } else {
            $data = BeanChangeRecord::where(['user_id' => $user_id, 'change_type' => $type]);
        }
        $data = $data->select(['id', 'bean', 'final_bean', 'type', 'change_type', 'created_at'])
            ->orderByDesc('id')
            ->paginate(20);
        return self::apiJson(200, 'ok', $data);
    }

    public function changeType()
    {
        return self::apiJson(200, 'ok', BeanChangeRecord::$fields['change_type']);
    }

    /**
     * 排行榜
     * @return \Illuminate\Http\JsonResponse
     */
    /*public function leaderboards()
    {
        $validator = Validator::make(request()->post(), [
            'date' => 'max:1',
        ],[
           'date.max' => '参数错误'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return response()->json([
                'code' => 500,
                'message' => $errors
            ]);
        }

        $date = request()->get('date');
        if ($date == 'w'){
            $data = BeanRecord::query()
                ->select('bean_records.user_id',DB::raw("SUM(bean_records.bean) AS total_bean"),'users.name','users.avatar')
                ->leftJoin('users','users.id','=','bean_records.user_id')
                ->where('bean_records.status',1)
                ->where(DB::raw("YEARWEEK(date_format(bean_records.updated_at,'%Y-%m-%d'))"),DB::raw("YEARWEEK(now())"))
                ->groupBy('bean_records.user_id')
                ->orderByDesc('total_bean')
                ->limit(20)
                ->get()
                ->toArray();
        } elseif ($date == 'm'){
            $data = BeanRecord::query()
                ->select('bean_records.user_id',DB::raw("SUM(bean_records.bean) AS total_bean"),'users.name','users.avatar')
                ->leftJoin('users','users.id','=','bean_records.user_id')
                ->where('bean_records.status',1)
                ->where(DB::raw("DATE_FORMAT(bean_records.updated_at,'%Y%m')"),DB::raw("DATE_FORMAT(CURDATE( ),'%Y%m')"))
                ->groupBy('bean_records.user_id')
                ->orderByDesc('total_bean')
                ->limit(20)
                ->get()
                ->toArray();
        } elseif ($date == 'd'){
            $data = BeanRecord::query()
                ->select('bean_records.user_id',DB::raw("SUM(bean_records.bean) AS total_bean"),'users.name','users.avatar')
                ->leftJoin('users','users.id','=','bean_records.user_id')
                ->where('bean_records.status',1)
                ->where(DB::raw("TO_DAYS(NOW()) - TO_DAYS(bean_records.updated_at)"),'=',1)
                ->groupBy('bean_records.user_id')
                ->orderByDesc('total_bean')
                ->limit(20)
                ->get()
                ->toArray();
        }
        //头像地址补全
        foreach ($data as $k => $v){
            $data[$k]['avatar'] = config('filesystems.disks.common.url').'/'.$v['avatar'];
        }

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }*/
}
