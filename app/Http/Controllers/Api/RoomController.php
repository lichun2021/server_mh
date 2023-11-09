<?php

namespace App\Http\Controllers\Api;

use App\Room;
use App\RoomUser;
use App\RoomAward;
use App\BoxRecord;
use App\BeanRecord;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['list', 'detail']]);
    }

    /**
     * 房间列表
     *
     * @param int $status 状态 1:进行中 2:已结束
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $validator = Validator::make(request()->post(), [
            'page' => ['integer', 'min:1'],
            'status' => ['integer', 'in:0,1']
        ], [
            'page.integer' => '参数类型错误',
            'status.integer' => '参数类型错误',
            'status.in' => '参数范围错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }

        $query = Room::with(['user:id,name,avatar','awards' => function($query){
            return $query->with(['box_record' => function($query){
                return $query->select(['id', 'name', 'cover', 'dura']);
            }])->select(['room_awards.id', 'room_awards.room_id', 'room_awards.box_record_id', 'box_records.bean'])
                ->join('box_records','box_records.id','=','room_awards.box_record_id')
                ->orderByDesc('box_records.bean');
        }])->select([
            'id',
            'user_id',
            'name',
            'describe',
            'end_time',
            'people_number',
            'status',
            'me_inviter',
            'type'
        ]);

        if (request()->has('status')) {
            $status = request()->get('status');
            switch ($status) {
                case 0:
                    $query->where('status', $status);
                    break;
                case 1:
                    $query->where('status', $status);
                    break;
                default:
                    $query->where('status', '>', -1);
            }
        } else {
            $query->where('status', '>', -1);
        }
        //->append(['dura_alias', 'lv_alias', 'lv_bgImage']);
        $rooms = $query->orderBy('status')
            ->orderBy('top', 'DESC')
            ->orderBy('type')
            ->orderBy('id', 'DESC')
            ->Paginate(20);
        $rooms->map(function ($item) {
            $item->setRelation('awards', $item->awards->take(3));
            return $item;
        });
        $rooms->append(['status_alias', 'join_number', 'awards_count', 'award_bean', 'is_pwd']);

        return self::apiJson(200,'ok',$rooms);
    }

    /**
     * 创建roll房
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function save()
    {
        $validator = Validator::make(request()->post(), [
            'name' => ['required', 'max:20'],
            'describe' => ['max:255'],
            'end_time' => ['required', 'date_format:Y-m-d H:i:s', 'after:+24 hours'],
            'people_number' => ['required', 'integer'],
            'password' => ['max:16'],
            'min_recharge' => ['regex:/^[0-9]+(.[0-9]{1,2})?$/'],
            'box_record_ids' => ['required', 'array', 'min:1'],
            'me_inviter' => ['in:0,1']
        ], [
            'name.required' => '请输入房间名',
            'name.max' => '房间名不能超过20个字符',
            'describe.max' => '房间描述不能超过255个字符',
            'end_time.required' => '请选择开奖时间',
            'end_time.date_format' => '开奖时间格式不正确',
            'end_time.after' => '开奖时间最少在 24 小时后',
            'people_number.required' => '请输入房间参与人数',
            'people_number.integer' => '参与人数错误',
            'min_recharge.regex' => '充值额度错误',
            'me_inviter.in' => '我的邀请者参数错误'
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }
        //主播禁止创建福利房
        if (auth('api')->user()->anchor != 1) {
            return self::apiJson(500,'您无创建roll房权限！');
        }
        //有充值金额限制 验证累
        /*
        if (request()->post('min_recharge') > 0){
            $validator = Validator::make(request()->post(), [
                'pay_start_time' => ['required','date_format:Y-m-d H:i:s']
            ], [
                'pay_start_time.required' => '有充值金额限制 请选择累计充值开始计算时间',
                'pay_start_time.date_format' => '输入累计充值开始时间格式错误'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'code' => 500,
                    'message' => $validator->errors()->first()
                ]);
            }
        }
        */

        try {
            DB::transaction(function () {
                $room = new Room();
                $room->user_id = auth('api')->id();
                $room->name = (string)request()->post('name');
                $room->describe = (string)request()->post('describe');
                $room->end_time = (string)request()->post('end_time');
                $room->people_number = (int)request()->post('people_number');
                $room->password = (string)request()->post('password');
                $room->pay_start_time = date('Y-m').'-01 00:00:00';
                $room->min_recharge = (float)request()->post('min_recharge');
                $room->me_inviter = (int)request()->post('me_inviter', 0);
                $room->save();

                //去除重复ID值
                $box_record_unique_ids = array_unique(request()->post('box_record_ids'));

                $box_record_ids = [];
                $total_bean = 0;
                foreach ($box_record_unique_ids as $box_record_id) {
                    $box_record = BoxRecord::find($box_record_id);
                    if (!$box_record || $box_record->user_id != auth('api')->id() || $box_record->status != 0) {
                        throw new \Exception("仓库饰品不存在", -1);
                    }
                    $total_bean += $box_record->bean;
                    $box_record_ids[] = $box_record->id;

                    $room_award = new RoomAward();
                    $room_award->room_id = $room->id;
                    $room_award->box_record_id = $box_record->id;
                    $room_award->save();
                }
                if ($total_bean < getConfig('room_min_total_bean')) {
                    throw new \Exception('创建roll房装备价值合计不得小于 ' . getConfig('room_min_total_bean') . getConfig('bean_name'), -1);
                }
                //将奖品变为冻结中
                BoxRecord::whereIn('id', $box_record_ids)->update(['status' => 3]);
            });
        } catch (\Exception $e) {
            $message = '创建失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }

            return self::apiJson(500,$message);
        }

        return self::apiJson(200,'房间创建成功！');
    }

    /**
     * 房间详情
     *
     * @param int $id ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make(request()->input(), [
            'id' => ['required', 'integer','min:1']
        ], [
            'id.required' => '缺少房间Id',
            'id.integer' => '房间Id错误',
            'id.min' => '房间Id错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }

        $room = Room::with([
            'user:id,name,avatar',
            'users' => function($query){
                return $query->with(['user:id,name,avatar'])->select(['id', 'room_id', 'user_id']);
            }, 'awards' => function($query){
                return $query->with(['box_record' => function($query){
                    return $query->with(['user:id,name,avatar'])
                        ->select(['id', 'name', 'cover', 'dura','user_id']);
                }])->select(['room_awards.id', 'room_awards.room_id', 'room_awards.box_record_id', 'box_records.bean'])
                    ->join('box_records','box_records.id','=','room_awards.box_record_id')
                    ->orderByDesc('box_records.bean');
            }])->where('id', request()->get('id'))
            ->where('status', '>', -1)
            ->first()
            ->makeHidden('password');
        if (!$room) {
            return self::apiJson(500,'房间不存在！');
        }

        //查看所有的用户
        /*$users = User::select('id', 'name', 'avatar')->whereIn('id', function ($query) {
            $query->select('user_id')->from('room_users')->where('room_id', request()->get('id'));
        })->get();*/
        //$room['room_users'] = $users;

        //查看所有奖励
        /*新逻辑 待启用
        $box_records = RoomAward::query()->with(['user:id,name,avatar'])->select([
            'room_awards.id as room_awards_id',
            'room_awards.box_record_id',
            'room_awards.get_user_id',
            'room_awards.created_at',
            'room_awards.updated_at',
            'box_records.id',
            'box_records.id',
            'box_records.name',
            'box_records.cover',
            'box_records.bean'
            ])
            ->leftJoin('box_records','box_records.id','room_awards.box_record_id')
            ->where('room_awards.room_id',request()->get('id'))
            ->get();
        foreach ($box_records as $key => $value){
            $box_records[$key]['cover'] = config('filesystems.disks.common.url').'/'.$value['cover'];
        }*/
        //老代码逻辑
        /*$box_records = BoxRecord::query()->with(['user:id,name,avatar'])->whereIn('id', function ($query) use ($room) {
            $query->select('box_record_id')->from('room_awards')->where('room_id', request()->get('id'));
        })->orderByDesc('bean')->get();*/
        //$box_records->append(['dura_alias', 'lv_alias', 'lv_bgImage']);
        //$room['box_records'] = $box_records;
        $room->append(['status_alias', 'join_number', 'award_bean', 'is_pwd']);
        return self::apiJson(200,'ok',$room);
    }

    /**
     * 加入房间
     *
     * @param int $id 房间ID
     * @param int $password 房间密码
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function join()
    {
        $validator = Validator::make(request()->post(), [
            'id' => ['required', 'integer','min:1'],
            'password' => ['max:16']
        ], [
            'id.required' => '缺少房间Id',
            'id.integer' => '房间Id错误',
            'id.min' => '房间Id错误',
            'password.max' => '房间密码错误'
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }

        try {
            DB::transaction(function () {
                $room = Room::where('id', request()->post('id'))->where('status', '>', -1)->lockForUpdate()->first();
                $user = auth('api')->user();
                if (empty($room)) {
                    throw new \Exception('房间不存在!', -1);
                } elseif ($user->anchor === 1) {
                    throw new \Exception('当前账户无法参与Roll房!', -1);
                }  elseif ($room->status != 0) {
                    throw new \Exception('房间已结束!', -1);
                } elseif ($room->people_number <= RoomUser::where('room_id', $room->id)->count()) {
                    throw new \Exception('房间参与人数已满!', -1);
                }  elseif (!empty(RoomUser::where('room_id', $room->id)->where('user_id', $user->id)->first())) {
                    throw new \Exception('您已加入该房间!', -1);
                } elseif ($user->is_roll === 0 && $room->me_inviter == 1 && $room->user_id != $user->id && $user->inviter_id != $room->user_id) {
                    throw new \Exception('房间只有主播邀请注册的用户和使用主播邀请码的用户才能进入!', -1);
                } elseif ($room->min_recharge > 0 && $user->is_roll === 0 && BeanRecord::where('user_id',$user->id)->where('status', 1)->where('created_at', '>', $room->pay_start_time)->sum('bean') < $room->min_recharge) {
                    throw new \Exception('自 ' . date('Y-m-d',strtotime($room->pay_start_time)) . ' 起充值达到 ' . $room->min_recharge . ' '.getConfig('bean_name') .' 才可进入该房间！', -1);
                } elseif (!empty($room->password) && $room->password != trim(request()->post('password'))) {
                    throw new \Exception('房间密码错误！', -1);
                }

                $isAddRoom = RoomUser::where('room_id', $room->id)->where('user_id', $user->id)->first();
                if (empty($isAddRoom)) {
                    RoomUser::create(['room_id' => $room->id, 'user_id' => $user->id]);
                }
            });
        } catch (\Exception $e) {
            $message = '进入房间失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }

            return self::apiJson(500,$message);
        }

        return self::apiJson(200,'进入房间成功!');
    }
}
