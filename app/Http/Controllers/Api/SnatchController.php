<?php

namespace App\Http\Controllers\Api;

use App\Skins;
use App\User;
use App\Snatch;
use App\BoxRecord;
use App\SnatchUser;
use App\SnatchAward;
use App\Jobs\TopHistory;
use App\BeanChangeRecord;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 幸运夺宝
 * Class SnatchController
 * @package App\Http\Controllers\Api
 * @author <860646000@qq.com>
 */
class SnatchController extends Controller
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
     * 列表
     * @param integer $page 页码
     * @param integer $status 状态
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $validator = Validator::make(request()->post(), [
            'page' => ['integer', 'min:1'],
            'status' => ['integer', 'in:0,1']
        ], [
            'page.integer' => '参数类型错误',
            'page.min' => '参数范围错误',
            'status.integer' => '参数类型错误',
            'status.in' => '参数范围错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }

        $query = Snatch::with(['award' => function ($query) {
            return $query->with(['skins_info' => function ($query) {
                return $query->select(['id', 'name', 'cover', 'bean', 'dura']);
            }])->select(['id', 'snatch_id', 'box_award_id', 'get_user_id']);
        }])->select([
            'id',
            'name',
            'brief',
            'user_max_num',
            'total_bean',
            'expend_bean',
            'status',
            'created_at',
            'updated_at'
        ])->orderByDesc('id');

        switch (request()->get('status')) {
            case 0:
                $data = $query->where('status', 0)->orderByDesc('id')->paginate(12);
                break;
            case 1:
                $data = $query->where('status', 1)->orderByDesc('id')->paginate(12);
                break;
            default:
                $data = $query->orderBy('status')->orderByDesc('id')->paginate(20);
        }
        //统计玩家人数
        foreach ($data as $key => $value) {
            $data[$key]['sold_num'] = SnatchUser::where('snatch_id', $value->id)->sum('num');
        }

        return self::apiJson(200,'ok',$data);
    }

    /**
     * 详情
     * @param integer $id 夺宝ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make([
            'id' => request()->get('id')
        ], [
            'id' => ['required', 'integer']
        ], [
            'id.required' => '请输入夺宝ID',
            'id.integer' => '夺宝ID错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }
        $data = Snatch::with(['award' => function ($query) {
            return $query->with(['skins_info' => function ($query) {
                return $query->select(['id', 'name', 'cover', 'bean', 'dura']);
            }, 'user' => function ($query) {
                return $query->select(['id', 'name', 'avatar']);
            }])->select(['id', 'snatch_id', 'box_award_id', 'get_user_id']);
        }, 'snatch_players' => function ($query) {
            return $query->with(['user_info' => function ($query) {
                return $query->select(['id', 'name', 'avatar',]);
            }])->select('id', 'snatch_id', 'user_id', 'num');
        }])->select([
            'id',
            'name',
            'brief',
            'user_max_num',
            'total_bean',
            'expend_bean',
            'status',
            'created_at',
            'updated_at'
        ])->where('id', request()->get('id'))->first();

        if (empty($data)) {
            return self::apiJson(500,'请求数据不存在');
        }

        return self::apiJson(200,'ok',$data);
    }

    /**
     * @param integer $id 夺宝ID
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function join()
    {
        $validator = Validator::make(request()->post(), [
            'id' => ['required', 'integer'],
            'num' => ['required', 'integer', 'min:1']
        ], [
            'id.required' => '请输入夺宝ID',
            'id.integer' => '输入夺宝ID错误',
            'num.required' => '请输入购买数量',
            'num.integer' => '购买数量输入有误',
            'num.min' => '购买数量输入有误',
        ]);

        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }

        try {
            DB::transaction(function () {
                $snatch_id = request()->post('id');
                $num = request()->post('num');
                $snatch = Snatch::where('id', $snatch_id)->lockForUpdate()->first();
                if (!$snatch) {
                    throw new \Exception('夺宝房不存在!', -1);
                }
                if ($snatch->status != 0) {
                    throw new \Exception('夺宝房已结束!', -1);
                }
                $snatch_user_num = SnatchUser::where(['snatch_id' => $snatch_id])->sum('num');
                $surplus = $snatch->user_max_num - $snatch_user_num;
                if ($num > $surplus) {
                    throw new \Exception('剩余份数不足，当前还剩 '.$surplus.' 份。', -1);
                }
                $user = User::where('id', auth('api')->id())->lockForUpdate()->first();
                $bean_total = bcmul($snatch->expend_bean, $num, 2);
                if ($user->bean < $bean_total) {
                    throw new \Exception(getConfig('bean_name').'不足', -1);
                } elseif ($user->is_recharge === 0){
                    throw new \Exception('进行任意额度充值即可开箱提取，为了维护广大用户的合法权益，防止机器人恶意注册,谢谢理解!', -1);
                }
                //减少金豆
                $user->decrement('bean', $bean_total);
                //增加亏损
                $user->increment('loss',$bean_total);
                //增加积分
                $user->increment('integral', $bean_total);
                //写记录
                BeanChangeRecord::add(0,12,-$bean_total);
                //加入夺宝
                $snatchUser = SnatchUser::where(['snatch_id' => $snatch_id, 'user_id' => $user->id])->first();
                if ($snatchUser){
                    $snatchUser->increment('num', $num);
                } else {
                    $snatch_user_model = new SnatchUser();
                    $snatch_user_model->snatch_id = $snatch_id;
                    $snatch_user_model->user_id = $user->id;
                    $snatch_user_model->num = $num;
                    $snatch_user_model->save();
                }

                $snatch_user_num = SnatchUser::where(['snatch_id' => $snatch_id])->sum('num');
                //开奖
                if ($snatch_user_num >= $snatch->user_max_num) {
                    $box_award_id = SnatchAward::where('snatch_id', $snatch_id)->first();
                    //抽奖列表
                    $snatch_user_list = [];
                    //玩家
                    $players = SnatchUser::where(['snatch_id' => $snatch_id])->get()->toArray();
                    foreach ($players as $player){
                        for ($i = 0;$i < $player['num'];$i++){
                            $snatch_user_list[] = $player['user_id'];
                        }
                    }
                    //打乱排序
                    shuffle($players);
                    //随机选取用户
                    if ($snatch->win_user_id != 0) {
                        $user_id = $snatch->win_user_id;
                    } else {
                        $u_k = array_rand($snatch_user_list,1);
                        $user_id = $snatch_user_list[$u_k];
                    }
                    //更新奖品获得者
                    $box_award_id->get_user_id = $user_id;
                    $box_award_id->save();
                    //更新房间状态
                    $snatch->status = 1;
                    $snatch->save();

                    $skins = Skins::find($box_award_id->box_award_id);
                    //减去战损
                    User::where('id', $user_id)->decrement('loss', $skins->bean);

                    $box_record = new BoxRecord();
                    $box_record->get_user_id = $user_id;
                    $box_record->user_id = $user_id;
                    $box_record->box_id = 0;
                    $box_record->box_name = '幸运夺宝';
                    $box_record->box_bean = bcmul($snatch->expend_bean,$snatch->user_max_num,2);
                    $box_record->skin_id = $skins->id;
                    $box_record->name = $skins->name;
                    $box_record->cover = $skins->getRawOriginal('cover');
                    $box_record->dura = $skins->dura;
                    $box_record->bean = $skins->bean;
                    $box_record->code = getUniqueOrderNumber();
                    $box_record->type = 6;
                    $box_record->is_purse = $skins->is_purse;
                    $box_record->save();
                    TopHistory::dispatch([$box_record->id]);
                }
            });
        } catch (\Exception $e) {
            $message = '加入失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            } else {
                //记录错误
                Log::error('加入夺宝失败', [
                    'Message' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                ]);
            }
            return self::apiJson(500,$message);
        }
        return self::apiJson(200,'加入成功');
    }
}
