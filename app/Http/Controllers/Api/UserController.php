<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Card;
use App\Bean;
use App\Room;
use App\RoomAward;
use App\BoxRecord;
use App\BeanRecord;
use App\Services\PayService;
use App\Services\YouPinService;
use App\Services\RechargeService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\SensitiveWordsService;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * 绑定手机号
     * @return \Illuminate\Http\JsonResponse
     */
    public function bindMobile()
    {
        $validator = Validator::make(request()->post(), [
            'mobile' => 'required|zh_mobile|unique:users,mobile',
        ], [
            'mobile.required' => '请输入手机号',
            'mobile.zh_mobile' => '手机号不正确',
            'mobile.unique' => '该手机号已被使用'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        $mobile = request()->post('mobile');
        $user = User::find(auth('api')->id());
        if (!empty($user->mobile)) {
            return self::apiJson(500, '您的账号已绑定过手机号，无需重复操作！');
        }

        $user->mobile = $mobile;
        $user->save();

        return self::apiJson(200, '绑定成功！');
    }

    /**
     * 设置Steam交易地址
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function setSteamUrl()
    {
        $validator = Validator::make(request()->post(), [
            'steam_url' => 'required|url',
        ], [
            'steam_url.required' => '请输入Steam交易地址',
            'steam_url.url' => 'Steam交易地址错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        try {
            //检测账号状态
            $steamUrl = request()->post('steam_url');
            $checkResp = YouPinService::checkTradeUrl($steamUrl);
            if ($checkResp['code'] !== 0) {
                throw new \Exception($checkResp['msg'], -1);
            } elseif ($checkResp['data']['status'] !== 1) {
                throw new \Exception($checkResp['data']['msg'], -1);
            }
            //获得Steam_Id
            $steam_url = parse_url($steamUrl);
            parse_str($steam_url['query'], $query_array);
            $steamId = $query_array['partner'] + 76561197960265728;

            DB::transaction(function () use ($steamId, $steamUrl) {
                $user = User::where(['id' => auth('api')->id()])->lockForUpdate()->first();
                /* 2021-1-1更新去除Steam 绑定限制*/
                $is_steam_url = User::where('steam_id', $steamId)
                    ->where('id', '!=', $user->id)
                    ->exists();

                //检测占用
                if ($is_steam_url) {
                    throw new \Exception('Steam 账号已被其他其他用户绑定', -1);
                }

                //绑定Steam 奖励上级
                /*if ($user->inviter_id > 0 && getConfig('reg_reward') == 1 && empty($user->steam_url) && empty($user->steam_id)) {
                    PromotionService::registerReward($user->inviter_id, $user->id);
                }*/
                //交易链接同一用户验证
                if (!empty($user->steam_id) && $user->steam_id != $steamId) {
                    throw new \Exception('只能绑定同一Steam账户的交易链接', -1);
                }
                //绑定Steam 赠送1刀
                /*if (empty($user->steam_url)) {
                    $user->increment('bean', getConfig('binding_steam_url_bonus'));
                    BeanChangeRecord::add(1, 13, getConfig('binding_steam_url_bonus'), $user->id);
                }*/

                $user->steam_url = $steamUrl;
                $user->steam_id = $steamId;

                if (!$user->save()) {
                    throw new \Exception('保存失败！', -1);
                }
            });
            return self::apiJson(200, 'Steam交易链接绑定成功！');
        } catch (\Exception $e) {
            $message = '操作失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
                //写错误日志
                \Log::warning('绑定Steam链接失败', [
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                    'Message' => $e->getMessage()
                ]);
            }
            return self::apiJson(500, $message);
        }
    }

    /**
     * 修改推广码
     * @return \Illuminate\Http\JsonResponse
     */
    public function setInvite_code()
    {
        $data = request()->post();

        $validator = Validator::make($data, [
            'code' => ['required', 'regex:/^[a-zA-Z0-9]+$/u', 'min:5', 'max:16', 'unique:users,invite_code,' . auth('api')->id()]
        ], [
            'code.required' => '请输入邀请码',
            'code.regex' => '邀请码只支持字母数字组合',
            'code.min' => '邀请码最少5个字符',
            'code.max' => '邀请码最大支持16个字符',
            'code.unique' => '邀请码已被占用',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        $user = auth('api')->user();
        $user->invite_code = $data['code'];

        if ($user->save()) {
            return self::apiJson(200, '操作成功！');
        }

        return self::apiJson(500, '系统错误，操作失败！');
    }

    /**
     * 绑定邀请人
     * @return \Illuminate\Http\JsonResponse
     */
    public function setInviter()
    {
        $data = request()->post();

        $validator = Validator::make($data, [
            'invite_code' => ['required', 'regex:/^[a-zA-Z0-9]+$/u', 'exists:users,invite_code']
        ], [
            'invite_code.required' => '请输入邀请码',
            'invite_code.exists' => '邀请码不存在',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        $invite_code = $data['invite_code'];
        $user = auth('api')->user();
        if (auth('api')->user()->anchor === 1) {
            return self::apiJson(500, '您的账号不支持绑定邀请人！');
        }

        if ($user->inviter_id != 0) {
            return self::apiJson(500, '您已绑定邀请人！');
        }

        $inviter = User::query()->where(['invite_code' => $invite_code])->first();
        if ($user->id == $inviter->id) {
            return self::apiJson(500, '邀请人不能为自己！');
        }

        $user->inviter_id = $inviter->id;
        if ($user->save()) {
            return self::apiJson(200, '操作成功！');
        }
        return self::apiJson(500, '系统错误，操作失败！');
    }

    /**
     * 更新头像URL
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAvatar()
    {
        $validator = Validator::make(request()->post(), [
            'path' => 'required|url|min:18|max:128',
        ], [
            'path.required' => '缺少头像链接',
            'path.url' => '头像链接错误',
            'path.min' => '头像链接错误',
            'path.max' => '头像链接错误'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        $user = auth('api')->user();
        $user->avatar = request()->post('path');

        if ($user->save()) {
            return self::apiJson(200, '操作成功！');
        }

        return self::apiJson(500, '系统错误，操作失败！');
    }

    /**
     * 更新昵称
     * @return \Illuminate\Http\JsonResponse
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     * @throws \DfaFilter\Exceptions\PdsSystemException
     */
    public function setName()
    {
        $validator = Validator::make(request()->post(), [
            'name' => 'required|min:2|max:8|regex:/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u|unique:users,name,' . auth('api')->id()
        ], [
            'name.required' => '请输入昵称',
            'name.min' => '昵称最少2个字符',
            'name.max' => '昵称不能超过10个字符',
            'name.regex' => '用户名只支持是汉字、字母、数字和下划线',
            'name.unique' => '昵称已被占用',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        $nickname = request()->post('name');
        //昵称敏感词过滤
        $is_word = SensitiveWordsService::getBadWord($nickname);
        if ($is_word) {
            return self::apiJson(500, '用户名包含敏感词 “' . implode('、', $is_word) . '” 请修改！');
        }

        $user = auth('api')->user();
        $user->name = $nickname;

        if ($user->save()) {
            return self::apiJson(200, '操作成功！');
        }

        return self::apiJson(500, '操作失败！');
    }

    /**
     * 个人仓库
     * @return \Illuminate\Http\JsonResponse
     */
    public function storage()
    {
        $validator = Validator::make(request()->input(), [
            'status' => 'integer|in:0,1,2,3,4,5,6',
            'sort' => 'integer|in:0,1'
        ], [
            'status.integer' => '参数类型错误',
            'status.in' => '参数范围错误',
            'sort.integer' => '参数类型错误',
            'sort.in' => '参数范围错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }

        if (request()->get('status')) {
            $status = request()->get('status');
        } else {
            $status = 0;
        }
        $sort = request()->get('sort');
        $sort = empty($sort) ? 'orderByDesc' : 'orderBy';

        $box_records = BoxRecord::query()->select(['id', 'name', 'cover', 'dura', 'lv', 'bean', 'status', 'back_message', 'created_at', 'updated_at'])
            ->where('user_id', auth('api')->id())
            ->where('status', $status)
            ->$sort('bean')
            ->orderByDesc('id')
            ->Paginate(24);

        $box_records->append(['status_alias']);

        return self::apiJson(200, 'ok', $box_records);
    }

    /**
     * 充值
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function recharge()
    {
        $validator = Validator::make(request()->post(), [
            'id' => ['required', 'integer'],
            'pay_way' => ['required', 'integer', 'in:1,2,7'],
        ], [
            'id.required' => '请选择充值额度',
            'id.integer' => '充值额度错误',
            'pay_way.required' => '请选择充值方式',
            'pay_way.integer' => '充值方式错误',
            'pay_way.in' => '充值方式错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        //对应充值记录
        $bean = Bean::query()->where('id', request()->post('id'))->where('is_putaway', 1)->first();
        if (!$bean) {
            return self::apiJson(500, '充值编号错误！');
        }

        //支付数据
        $pay_data = [];
        $pay_way = request()->post('pay_way');
        try {
            DB::transaction(function () use ($bean, &$pay_data, $pay_way) {
                //订单号
                $code = date('YmdHis') . random_int(1000, 9999);
                //写入订单
                $user = auth('api')->user();
                $bean_record = new BeanRecord();
                $bean_record->user_id = $user->id;
                $bean_record->inviter_id = $user->inviter_id;
                $bean_record->bean = $bean->bean;
                $bean_record->price = $bean->price;
                $bean_record->finally_price = bcsub($bean->price, bcmul($bean->price, 0.03, 2), 2);
                $bean_record->code = $code;
                $bean_record->status = 0;
                $bean_record->save();
                //下单
                $result = PayService::order($code, $pay_way, $bean->price, 'PanDaCSGO账户充值' . $bean->bean . getConfig('bean_name'), $bean->product_id);
                
                if ($result['code'] == 0) {
                    throw new \Exception($result['message'], -1);
                }

                $pay_data = [
                    'pay_way' => $pay_way,
                    'type' => 'url',
                    'list' => $result['data']
                ];
            });
        } catch (\Exception $e) {
            $message = '系统错误，下单失败！';
            //var_dump($pay_data['html']);die;
            //$message = $e->getMessage().$e->getFile().$e->getLine();
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }

            return self::apiJson(500, $message);
        }

        return self::apiJson(200, 'ok', $pay_data);
    }

    /**
     * 卡密充值
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function cardRecharge()
    {
        $validator = Validator::make(request()->post(), [
            'card' => ['required', 'size:32'],
        ], [
            'card.required' => '卡密不能为空',
            'card.size' => '卡密错误'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        try {
            DB::transaction(function () {
                $card = Card::where('number', request()->post('card'))->where('status', 0)->lockForUpdate()->first();
                if (empty($card)) {
                    throw new \Exception("卡密错误", -1);
                }
                RechargeService::run(auth('api')->id(), $card->bean, true, $card->number);
                $card->status = 1;
                $card->save();
            });
        } catch (\Exception $e) {
            $message = '充值失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }

            return self::apiJson(500, $message);
        }
        return self::apiJson(200, '充值成功');
    }

    /**
     * 充值记录
     * @return \Illuminate\Http\JsonResponse
     */
    public function rechargeRecord()
    {
        $bean_record = BeanRecord::select(
            'bean',
            'price',
            'code',
            'status'
        )->where('user_id', auth('api')->id())->where('status', 1)->orderBy('id', 'DESC')->Paginate(20);

        $bean_record->append('status_alias');

        return self::apiJson(200, 'ok', $bean_record);
    }

    /**
     * 参加房间记录
     * @return \Illuminate\Http\JsonResponse
     */
    public function room()
    {
        $rooms = Room::with(['user:id,name,avatar'])->select([
            'id',
            'user_id',
            'name',
            'describe',
            'end_time',
            'people_number',
            'status'
        ])->whereIn('id', function ($query) {
            $query->select('room_id')->from('room_records')->where('user_id', auth('api')->id());
        })->orderBy('id', 'DESC')->Paginate(10);
        //查看所有奖励
        foreach ($rooms->items() as &$room) {
            //奖品获得者
            $get_user = RoomAward::select('box_record_id', 'get_user_id', 'users.name as get_user_name', 'users.avatar as get_user_avatar')
                ->where('room_id', $room->id)
                ->leftJoin('users', 'users.id', '=', 'room_awards.get_user_id')
                ->get()->toArray();
            //所有奖品
            $box_records = BoxRecord::select('id', 'name', 'cover', 'dura', 'lv')->whereIn('id', function ($query) use ($room) {
                $query->select('box_record_id')->from('room_awards')->where('room_id', $room->id);
            })->get();

            $get_user = array_column($get_user, null, 'box_record_id');


            foreach ($box_records as $key => $box_record) {
                if (!empty($get_user[$box_record->id])) {
                    unset($get_user[$box_record->id]['box_record_id']);
                    $get_user_one = $get_user[$box_record->id];
                    $get_user_one['get_user_avatar'] = config('filesystems.disks.common.url') . '/' . $get_user_one['get_user_avatar'];
                    $box_record['get_user'] = $get_user_one;
                }
                $box_records[$key] = $box_record;
            }
            $room['box_records'] = $box_records;
        }

        $rooms->append(['status_alias', 'join_number', 'award_bean']);

        return response()->json([
            'code' => 200,
            'data' => $rooms
        ]);
    }

    /**
     * 赠送礼物
     *
     * @param int $id 仓库记录ID
     * @param string $invite_code 邀请码
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function giveBack()
    {
        $validator = Validator::make(request()->post(), [
            'data' => ['required', 'array'],
            'invite_code' => ['required']
        ], [
            'data.required' => '请选择仓库饰品',
            'data.array' => '饰品数据错误',
            'invite_code.required' => '请输入对方邀请码',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return response()->json([
                'code' => 500,
                'message' => $errors
            ]);
        }

        //禁止赠送提示
        if (auth()->user()->close_gift == 1) {
            if (auth()->user()->anchor == 1) {
                return response()->json([
                    'code' => 500,
                    'message' => '您的赠送功能已关闭'
                ]);
            } else {
                return response()->json([
                    'code' => 500,
                    'message' => '您的赠送功能已关闭'
                ]);
            }
        }
        try {
            DB::transaction(function () {
                $data = request()->post('data');
                $user = User::where('invite_code', request()->post('invite_code'))->first();
                if (!$user) {
                    throw new \Exception("用户不存在", -1);
                }

                foreach ($data as $key => $value) {
                    $box_record = BoxRecord::where('id', $value)
                        ->where('user_id', auth('api')->id())
                        ->lockForUpdate()
                        ->first();
                    if (!$box_record || $box_record->status != 0) {
                        throw new \Exception("物品不存在或不能操作", -1);
                    }
                    $box_record->user_id = $user->id;
                    $box_record->save();
                }
            });
        } catch (\Exception $e) {
            $message = '操作失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }

            return response()->json([
                'code' => 500,
                'message' => $message
            ]);
        }

        return response()->json([
            'code' => 200,
            'message' => '赠送成功'
        ]);
    }

    /**
     * 用户信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function info()
    {
        $user_id = auth()->id();
        $key = 'user_' . $user_id . '_info';
        $data = \Cache::get($key);
        if ($data === null) {
            $date = date('Y-m');
            $monthRecharge = BeanRecord::query()->where(['user_id' => $user_id, 'status' => 1])->where('created_at', 'LIKE', $date . '%')->sum('bean');
            $monthExtract = BoxRecord::query()->where(['user_id' => $user_id, 'status' => 1])->where('updated_at', 'LIKE', $date . '%')->sum('bean');
            $totalRecharge = BeanRecord::query()->where(['user_id' => $user_id, 'status' => 1])->sum('bean');
            $totalExtract = BoxRecord::query()->where(['user_id' => $user_id, 'status' => 1])->sum('bean');
            $data = [
                'month_recharge' => $monthRecharge,
                'month_extract' => $monthExtract,
                'total_recharge' => $totalRecharge,
                'total_extract' => $totalExtract,
            ];
            \Cache::add($key, $data, 300);
        }
        return self::apiJson(200, 'ok', $data);
    }
}
