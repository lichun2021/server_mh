<?php

namespace App\Http\Controllers\Api;

use App\BeanChangeRecord;
use App\BoxRecord;
use App\Jobs\TopHistory;
use App\Skins;
use App\StarsContain;
use App\StarsList;
use App\Services\StarWarsService;
use App\Http\Controllers\Controller;
use App\StarsRecord;
use App\User;
use Illuminate\Support\Facades\Validator;

/**
 * Class StarWarsController
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2021/9/28
 * Time：23:20
 */
class StarWarsController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['index', 'detail']]);
    }

    /**
     * 星星列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = StarsList::select(['id', 'name', 'cover', 'bean'])
            ->where(['status' => 1])
            ->get()
            ->toArray();
        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer|min:1|exists:stars_list,id',
        ], [
            'id.required' => '缺少Id',
            'id.integer' => 'Id错误',
            'id.min' => 'Id错误',
            'id.exists' => 'Id错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }
        $id = request()->get('id');
        $nums = [
            1 => 1,
            2 => 2,
            3 => 4,
            4 => 8,
            5 => 16,
            6 => 32,
        ];
        $star = StarsList::select(['id', 'name', 'cover', 'bean'])->where('id', $id)->first()->toArray();
        //用户进度
        if (auth('api')->user()) {
            $user_id = auth('api')->id();
            $orderId = StarWarsService::getOrderId($id, $user_id);
            $user_progress = StarsRecord::with(['skins' => function ($query) {
                return $query->select(['id', 'name', 'cover', 'dura', 'bean']);
            }])->select(['id', 'user_id', 'stars_id', 'seat', 'skin_id', 'skin_lv', 'bean'])
                ->where(['user_id' => $user_id, 'stars_id' => $id, 'order_id' => $orderId])
                ->orderBy('seat')
                ->get()
                ->toArray();
            $star['user_progress'] = $user_progress;
        }
        //详情
        $data = [];
        for ($i = 0; $i < 6; $i++) {
            $val = $i + 1;
            StarsContain::$seat = $val;
            $one = [
                'seat' => $val,
                'bean' => bcmul($star['bean'], $nums[$val], 2),
                'awards' => StarsContain::query()->with(['skins' => function ($query) {
                    return $query->select(['id', 'name', 'cover', 'dura', 'bean']);
                }])->select(['id', 'stars_id', 'skin_id', 'l' . $val . ' as lv'])
                    ->where(['stars_id' => $id, 'v' . $val => 1])
                    ->get()
                    ->append(['odds_percent'])
                    ->toArray()
            ];
            if (count($one['awards']) !== 8){
                return self::apiJson(500, '配置错误');
            }
            $data[] = $one;
        }
        $star['list'] = $data;
        return self::apiJson(200, 'ok', $star);
    }

    /**
     * 开启
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function open()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer|min:1',
            'seat' => 'required|integer|min:1|max:6',
        ], [
            'id.required' => '缺少Id',
            'id.integer' => 'Id错误',
            'id.min' => 'Id错误',
            'seat.required' => '缺少Seat',
            'seat.min' => 'Seat错误',
            'seat.max' => 'Seat错误',

        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }
        \DB::beginTransaction();
        try {
            $user_id = auth('api')->id();
            $id = request()->post('id');
            $seat = request()->post('seat');

            $stars = StarsList::where(['status' => 1, 'id' => $id])->first();
            if (!$stars) {
                throw new \Exception('Id错误！', -1);
            }

            $orderId = StarWarsService::getOrderId($id, $user_id);

            $isSeat = StarsRecord::where(['stars_id' => $id, 'user_id' => $user_id, 'order_id' => $orderId, 'seat' => $seat])->exists();
            if ($isSeat) {
                throw new \Exception('此坐标已开启！', -1);
            }
            $starsRecord = StarsRecord::query()->where(['stars_id' => $id, 'user_id' => $user_id, 'order_id' => $orderId])->orderByDesc('seat')->first();
            if (!$starsRecord && $seat != 1) {
                throw new \Exception('开启序号检测失败，请按顺序开启！', -1);
            } elseif ($starsRecord && $starsRecord->seat != ($seat - 1)) {
                throw new \Exception('开启序号检测失败，请按顺序开启！', -1);
            }

            $num = StarsRecord::where(['order_id' => $orderId])->count('id');
            if ($num > 0) {
                if ($num === 1) {
                    $total_bean = bcmul($stars->bean, 2, 2);
                } elseif ($num === 2) {
                    $total_bean = bcmul($stars->bean, 4, 2);
                } elseif ($num === 3) {
                    $total_bean = bcmul($stars->bean, 8, 2);
                } elseif ($num === 4) {
                    $total_bean = bcmul($stars->bean, 16, 2);
                } elseif ($num === 5) {
                    $total_bean = bcmul($stars->bean, 32, 2);
                }
            } else {
                $total_bean = $stars->bean;
            }

            $user = User::where('id', $user_id)->lockForUpdate()->first();
            if ($user->bean < $total_bean) {
                throw new \Exception(getConfig('bean_name') . '不足！', -1);
            } elseif ($user->is_recharge === 0){
                throw new \Exception('进行任意额度充值即可开箱提取，为了维护广大用户的合法权益，防止机器人恶意注册,谢谢理解!', -1);
            }
            //扣除金币
            $user->decrement('bean', $total_bean);
            //写收支明细
            BeanChangeRecord::add(0, 15, -$total_bean);
            //增加亏损
            $user->increment('loss', $total_bean);
            //增加积分
            $user->increment('integral', $total_bean);
            $num++;

            $skin = explode('|', StarWarsService::getSkin($id, $num));
            $skin_id = $skin[0];
            $lv = $skin[1];
            //入库
            $box_record = new BoxRecord();
            $skins = Skins::find($skin_id);
            //减去亏损
            $user->decrement('loss', $skins->bean);

            $box_record->get_user_id = $user_id;
            $box_record->user_id = $user_id;
            $box_record->box_id = 0;
            $box_record->box_name = '红星轮盘';
            $box_record->box_bean = $total_bean;
            $box_record->skin_id = $skins->id;
            $box_record->name = $skins->name;
            $box_record->cover = $skins->getRawOriginal('cover');
            $box_record->dura = $skins->dura;
            $box_record->lv = $lv;
            $box_record->bean = $skins->bean;
            $box_record->code = getUniqueOrderNumber();
            $box_record->type = 8;
            $box_record->is_purse = $skins->is_purse;
            $box_record->save();
            $data = $box_record->makeHidden(['get_user_id', 'user_id', 'box_id', 'box_name', 'box_bean', 'uuid', 'type', 'created_at', 'updated_at']);
            //写开启记录
            $stars_record = new StarsRecord();
            $stars_record->user_id = $user_id;
            $stars_record->stars_id = $id;
            $stars_record->order_id = $orderId;
            $stars_record->seat = $seat;
            $stars_record->skin_id = $skin_id;
            $stars_record->skin_lv = $lv;
            $stars_record->bean = $total_bean;
            $stars_record->skin_bean = $skins->bean;
            $stars_record->save();
            if ($num >= 6) {
                \Cache::delete(StarWarsService::getCacheKey($id, $user_id));
            }
            //加入列队
            TopHistory::dispatch([$box_record->id]);
            \DB::commit();
            return self::apiJson(200, 'ok', $data);
        } catch (\Exception $e) {
            \DB::rollback();
            $message = '系统错误';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            } else {
                //写错误日志
                \Log::debug('==========红星轮盘失败===========', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'user_id' => auth('api')->id()]);
            }
            return self::apiJson(500, $message);
        }
    }

    /**
     * 重置
     * @return \Illuminate\Http\JsonResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function reset()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer|min:1',
        ], [
            'id.required' => '缺少Id',
            'id.integer' => 'Id错误',
            'id.min' => 'Id错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }
        $id = request()->post('id');
        $stars = StarsList::where(['id' => $id])->first();
        if (!$stars) {
            return self::apiJson(500, 'Id错误！');
        }
        \Cache::delete(StarWarsService::getCacheKey($id, auth('api')->id()));
        return self::apiJson(200, '重置成功！');
    }
}
