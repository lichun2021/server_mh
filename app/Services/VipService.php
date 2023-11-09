<?php

namespace App\Services;

use App\Vip;
use App\User;
use App\UserRewardLog;
use App\BeanChangeRecord;

/**
 * Class VipService
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/7/2
 * Time：15:14
 */
class VipService
{
    /**
     * VIP升级 和 VIP充值返佣
     * @param int $user_id
     * @param numeric $bean
     */
    public static function upgradeVip($user_id, $bean)
    {
        $user = User::query()->where(['id' => $user_id])->first();
        $before_bean = $user->total_recharge - $bean;
        $surplus_bean = $bean;

        foreach (Vip::getList() as $vip) {
            if ($vip['level'] > $user->vip_level && $user->total_recharge >= $vip['threshold']) {
                $user->vip_level = $vip['level'];
                $user->save();
                //VIP升级奖励
                $user->increment('bean', $vip['packet']);
                //写记录
                $log = new UserRewardLog();
                $log->user_id = $user_id;
                $log->type = 7;
                $log->bean = $vip['packet'];
                $log->save();
                //写收支明细
                BeanChangeRecord::add(1, 17, $vip['packet'], $user_id);
                //结算中间返利
                $zj_bean = $vip['threshold'] - $before_bean;
                $before_bean = $vip['threshold'];
                $surplus_bean = $surplus_bean - $zj_bean;
                //查询上一级奖励比率
                $vipModel = Vip::query()->where(['level' => $user->vip_level - 1])->first();
                if ($vipModel->rebate > 0){
                    $jl_bean = bcmul($zj_bean, $vipModel->rebate / 100, 2);
                    $user->increment('bean', $jl_bean);
                    //写记录
                    $log = new UserRewardLog();
                    $log->user_id = $user_id;
                    $log->type = 6;
                    $log->bean = $jl_bean;
                    $log->save();
                    //写收支明细
                    BeanChangeRecord::add(1, 16, $jl_bean, $user_id);
                }
            }
        }
        //剩余金币按等级返佣
        $vipModel = Vip::query()->where(['level' => $user->vip_level])->first();
        if ($surplus_bean > 0 && $vipModel->rebate > 0){
            $jlBean = bcmul($surplus_bean, $vipModel->rebate / 100, 2);
            $user->increment('bean', $jlBean);
            //写记录
            $log = new UserRewardLog();
            $log->user_id = $user_id;
            $log->type = 6;
            $log->bean = $jlBean;
            $log->save();
            //写收支明细
            BeanChangeRecord::add(1, 16, $jlBean, $user_id);
        }
    }
}
