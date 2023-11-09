<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/23
 * Time: 1:25
 */

namespace App\Http\Controllers\Api;

use App\User;
use App\BeanRecord;
use App\PromotionLevel;
use App\FirstRechargeOffer;
use App\Http\Controllers\Controller;
class PromoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth',['except' => ['first']]);
    }

    /**
     * 合作推广
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $pl_list = PromotionLevel::query()->get()->toArray();
        $level = array_column($pl_list, null, 'level');
        $gain = BeanRecord::where(['inviter_id' => auth('api')->id(), 'status' => 1])->sum('bean');
        //$be_invite_number = DB::select('SELECT count(*) AS count FROM (SELECT COUNT(id) FROM bean_records WHERE inviter_id = :user_id AND status = 1 GROUP BY user_id) gg',['user_id' => auth('api')->id()]);
        $be_invite_number = User::query()->where('inviter_id',auth('api')->id())->count('id');

        /*$welfare_promotion = Welfare::select(['id','name','description','box_id','type','promotion_level'])->with(['box:id,name'])->where(function ($q){
            return $q->Where('type',2)->orWhere('type',3);
        })->orderBy('sort')->get()->toArray();

        foreach ($welfare_promotion as $key => $item){
            if ($item['type'] == 2){
                $welfare_promotion[$key]['my_times'] = WelfarePromotion::query()->where(['user_id' => auth('api')->id(), 'type' => $item['type']])->sum('f');
            } else {
                $welfare_promotion[$key]['my_times'] = WelfarePromotion::query()->where(['user_id' => auth('api')->id(), 'type' => $item['type']])->sum(Welfare::$fields['promotion_field'][$item['promotion_level']]);
            }
        }*/

        $user = [
            'promotion_level' => auth('api')->user()->promotion_level,
            'invite_code' => auth('api')->user()->invite_code,
            'people_invited_num' => $be_invite_number,
            'invite_total_recharge' => $gain,
            'promotion' => $level[auth('api')->user()->promotion_level],
        ];

        $data = [
            'user' => $user,
            'list' => $pl_list,
            //'welfare_promotion' => $welfare_promotion
        ];

        return self::apiJson(200,'ok',$data);
    }

    /**
     * 首冲奖励
     * @return \Illuminate\Http\JsonResponse
     */
    public function first()
    {
        $data = FirstRechargeOffer::query()
            ->select(['price','bean','ratio','description'])
            ->leftJoin('beans','beans.id','=','beans_id')
            ->orderBy('price')
            ->get()->toArray();

        foreach ($data as $key => $one){
            $data[$key]['arrive'] = $one['bean'] + ($one['bean'] *  ($one['ratio'] / 100));
        }

        return self::apiJson(200,'ok',$data);
    }
}
