<?php

namespace App\Http\Controllers\Api;

use App\Vip;
use App\User;
use App\Http\Controllers\Controller;

class VipController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['list']]);
    }

    /**
     * VIP列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        return response()->json([
            'code' => 200,
            'message' => 'ok',
            'data' => Vip::get()
                ->append('level_alias')
                ->toArray()
        ]);
    }
    
    /**
     * VIP列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists()
    {
        $user_id = auth('api')->id();
        $user = User::where(['id' => $user_id])->first();
        $nextLevelVip = Vip::query()->where(['level' => $user->vip_level + 1])->first();

        if (empty($nextLevelVip)) {
            $tips = [];
        } else {
            $tips = [
                'next_level' => $nextLevelVip->level_alias,
                'next_rebate' => $nextLevelVip->rebate,
                'next_packet' => $nextLevelVip->packet,
                'next_lack' => number_format($nextLevelVip->threshold - $user->total_recharge, 2),
            ];
        }
        $list = Vip::get()
            ->append('level_alias')
            ->toArray();
        return response()->json([
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'tips' => $tips,
                'list' => $list
            ]
        ]);
    }
}
