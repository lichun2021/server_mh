<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/21 15:34
 */

namespace App\Services;

use App\User;
use App\PromotionLevel;
use App\UserRewardLog;
use App\BeanChangeRecord;

/**
 * 推广活动服务层
 * Class PromotionService
 * @package App\Services
 */
class PromotionService
{
    /**
     * @var \App\PromotionLevel
     */
    public static $PromotionLevel;

    /**
     * 注册的奖励
     * @param $inviter_id integer 邀请人ID
     * @param $next_user_id integer 用户ID
     */
    public static function registerReward($inviter_id, $next_user_id)
    {
        $user = User::where('id', $inviter_id)->first();
        if (!empty($user)) {
            $promotionLevel = PromotionLevel::getOne($user->promotion_level);
            if ($promotionLevel) {
                $user->increment('bean', $promotionLevel->reg_rebate);
                //写入记录
                $log = new UserRewardLog();
                $log->user_id = $user->id;
                $log->type = 1;
                $log->next_user_id = $next_user_id;
                $log->bean = $promotionLevel->reg_rebate;
                $log->save();
                //写收支记录
                BeanChangeRecord::add(1,8,$promotionLevel->reg_rebate,$user->id);
            }
        }
    }

    /**
     * 下级累计充值升级
     * @param $user_id integer 用户id
     */
    public static function levelUp($inviter_id)
    {
        $userInviterTotal = User::where(['inviter_id' => $inviter_id])->sum('total_recharge');
        if ($userInviterTotal > 0) {
            $user = User::where(['id' => $inviter_id])->first();
            foreach (PromotionLevel::getList() as $promotionLevel) {
                if (!empty($user) && $promotionLevel['invite_total'] != 0 && $userInviterTotal >= $promotionLevel['invite_total'] && $promotionLevel['level'] > $user->promotion_level) {
                    $user->promotion_level = $promotionLevel['level'];
                    $user->save();
                }
            }
        }
    }

    /**
     * 用户充值升级
     * @param integer $user_id 用户id
     */
    public static function rechargelevelUp($user_id)
    {
        $user = User::where(['id' => $user_id])->first();
        if ($user->total_recharge > 0) {
            foreach (PromotionLevel::getList() as $promotionLevel) {
                if ($promotionLevel['total'] != 0 && $user->total_recharge >= $promotionLevel['total']) {
                    User::where('id', $user_id)->update(['promotion_level' => $promotionLevel['level']]);
                }
            }
        }
    }

    /**
     * 充值给上级用户返佣
     * @param $prev_user_id integer 上级用户ID
     * @param $bean
     */
    public static function prevReward($user_id, $prev_user_id, $bean)
    {
        $user = User::where('id', $prev_user_id)->first();
        if ($user) {
            $promotionLevel = PromotionLevel::getOne($user->promotion_level);
            $rebate = ($promotionLevel->rebate / 100);
            $rewardBean = bcmul($bean, $rebate, 2);
            User::where('id', $prev_user_id)->increment('bean', $rewardBean);
            //写入记录
            $log = new UserRewardLog();
            $log->user_id = $prev_user_id;
            $log->type = 2;
            $log->next_user_id = $user_id;
            $log->charge_bean = $bean;
            $log->bean = $rewardBean;
            $log->save();
            //写收支记录
            BeanChangeRecord::add(1,7,$rewardBean,$prev_user_id);
        }
    }
}
