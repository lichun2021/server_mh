<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/11/12
 * Time: 0:19
 */

namespace App\Services;

use App\BeanChangeRecord;
use App\User;
use App\Bean;
use App\BeanRecord;
use App\UserRewardLog;
use App\FirstRechargeOffer;

class RechargeService
{
    /**
     * 充值到账处理
     * @param integer $user_id 用户ID
     * @param int $bean 金豆
     * @param bool $bean_log 开启充值记录写入
     * @throws \Exception
     */
    public static function run($user_id, $bean, $bean_log = true, $card = '')
    {
        //入账
        $user = User::where('id', $user_id)->lockForUpdate()->first();

        $beanList = Bean::query()->where('bean', $bean)->first();
        if (empty($beanList)) {
            throw new \Exception('充值金额与充值列表面值不对应！', -1);
        }

        $totalRechargeBean = $user->total_recharge;
        //正常充值
        $user->increment('bean', $bean);
        $user->increment('total_recharge', $bean);
        //写收支明细
        BeanChangeRecord::add(1, 5, $bean, $user->id);

        //首次充值给予上级推广注册奖励
        if ($user->inviter_id && $totalRechargeBean == 0 && getConfig('reg_reward') == 1) {
            PromotionService::registerReward($user->inviter_id, $user->id);
        }
        //首冲奖励
        if ($user->is_recharge == 0 && getConfig('first_recharge') == 1) {
            $firstRechargeReward = FirstRechargeOffer::query()->where('beans_id', $beanList->id)->first();
            if ($firstRechargeReward) {
                $beanReward = $bean * ($firstRechargeReward->ratio / 100);
                $user->increment('bean', $beanReward);
                //写入记录首冲奖励记录
                $log = new UserRewardLog();
                $log->user_id = $user->id;
                $log->type = 3;
                $log->next_user_id = null;
                $log->bean = $beanReward;
                $log->save();
                //写收支明细
                BeanChangeRecord::add(1, 6, $beanReward, $user->id);
            }
        }
        //取消首冲资格
        $user->is_recharge = 1;
        $user->save();
        
        //用户充值升级
        VipService::upgradeVip($user_id, $bean);

        //奖励推荐人
        if ($user->inviter_id && getConfig('promotion_level') == 1) {
            PromotionService::prevReward($user->id, $user->inviter_id, $bean);
        }

        //下级累计充值升级
        if ($user->inviter_id) {
            PromotionService::levelUp($user->inviter_id);
            /*$inviter_user = User::find($user->inviter_id);
            //下级每充值奖励开箱福利
            $welfare_promotion = WelfarePromotion::query()->where(['user_id' => $user->inviter_id, 'to_user_id' => $user->id, 'type' => 3])->first();
            $every_recharge = Welfare::query()->where(['type' => 3])->orderBy('promotion_level')->pluck('promotion_level')->toArray();
            if ($welfare_promotion){
                if ($welfare_promotion->lv < 4){
                    foreach ($every_recharge as $key => $value){
                        if ($user->total_recharge >= $value && $key > $welfare_promotion->lv){
                            $lv = Welfare::$fields['every'][$key];
                            $welfare_promotion->$lv = 1;
                            $welfare_promotion->lv = $key;
                            $welfare_promotion->save();
                            //钥匙计数
                            $inviter_user->increment('box_key_total',1);
                        }
                    }
                }
            } else {
                $welfare_promotion = new WelfarePromotion();
                $welfare_promotion->user_id = $user->inviter_id;
                $welfare_promotion->to_user_id = $user->id;
                $welfare_promotion->type = 3;
                $welfare_promotion->save();
                foreach ($every_recharge as $key => $value){
                    if ($user->total_recharge >= $value){
                        $lv = Welfare::$fields['every'][$key];
                        $welfare_promotion->$lv = 1;
                        $welfare_promotion->lv = $key;
                        $welfare_promotion->save();
                        //钥匙计数
                        $inviter_user->increment('box_key_total',1);
                    }
                }
            }*/
        }
        //用户充值升级
        //PromotionService::rechargelevelUp($user->id);

        //充值解封非主播用户赠送和提货
        if ($user->close_gift == 1 && $user->anchor == 0 || $user->ban_pick_up == 1 && $user->anchor == 0) {
            $user->close_gift = 0;
            $user->ban_pick_up = 0;
            $user->save();
        }

        //写充值记录
        if ($bean_log) {
            if (empty($beanList)) {
                $beanList = Bean::where('bean', $bean)->first();
            }
            $beanRecord = new BeanRecord();
            $beanRecord->user_id = $user_id;
            $beanRecord->inviter_id = $user->inviter_id;
            $beanRecord->bean = $bean;
            $beanRecord->price = $beanList->price;
            $beanRecord->finally_price = bcsub($beanList->price, bcmul($beanList->price, 0.03, 2), 2);
            $beanRecord->code = date('YmdHis') . random_int(1000, 9999);
            $beanRecord->trade_no = $card;
            $beanRecord->is_pay_api = 1;
            $beanRecord->status = 1;
            $beanRecord->save();
        }
    }
}
