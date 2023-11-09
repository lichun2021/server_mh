<?php

namespace App\Services;

use App\SmsCode;

class SmsService
{
    /**
     * @var string
     */
    private static $_errorMsg;

    /**
     * 发送短信
     * @param $phoneNumber
     * @return bool
     * @throws \Exception
     */
    public static function sendSms($phoneNumber)
    {
        // \DB::beginTransaction();
        // try {
        //     $ip = request()->ip();
        //     $second = SmsCode::where(['mobile' => $phoneNumber, 'status' => 1])
        //         ->orderByDesc('id')
        //         ->first();
        //     if ($second && (time() - strtotime($second->created_at)) <= 60) {
        //         \DB::rollBack();
        //         self::$_errorMsg = '操作频繁，请稍后再试！';
        //         return false;
        //     }
        //     $todayQuantity = SmsCode::where(['mobile' => $phoneNumber, 'status' => 1])
        //         ->where('created_at', 'like', date('Y-m-d') . '%')
        //         ->count('id');
        //     if ($todayQuantity > 20) {
        //         \DB::rollBack();
        //         self::$_errorMsg = '手机号已达到今日最大短信额度';
        //         return false;
        //     }
        //     $ipQuantity = SmsCode::where(['ip' => $ip, 'status' => 1])
        //         ->where('created_at', 'like', date('Y-m-d') . '%')
        //         ->count('id');
        //     if ($ipQuantity > 50) {
        //         \DB::rollBack();
        //         self::$_errorMsg = '发送失败,客户端已达到今日最大短信额度！';
        //         return false;
        //     }

        //     $code = random(6, true);
        //     $resp = SubMailService::sendSms($phoneNumber, $code);

        //     $is_success = $resp['status'] === 'success' ? true : false;
        //     $model = new SmsCode();
        //     $model->mobile = $phoneNumber;
        //     $model->code = $code;
        //     $model->response = $resp;
        //     $model->isuse = 0;
        //     $model->ip = $ip;
        //     $model->status = $is_success ? 1 : 0;
        //     $model->save();

        //     if (!$is_success) {
        //         self::$_errorMsg = $resp['msg'];
        //     }
        //     \DB::commit();
        //     return $is_success;
        // } catch (\Exception $e) {
        //     \DB::rollBack();
        //     self::$_errorMsg = $e->getMessage();
        //     return false;
        // }
        \DB::beginTransaction();
        try {
            $sms = new YunXinSmsService();
            
            
            $ip = request()->ip();
            
            $second = SmsCode::where(['mobile' => $phoneNumber, 'status' => 1])
                ->orderByDesc('id')
                ->first();
            if ($second && (time() - strtotime($second->created_at)) <= 60) {
                \DB::rollBack();
                self::$_errorMsg = '操作频繁，请稍后再试！';
                return false;
            }
            
            $todayQuantity = SmsCode::where(['mobile' => $phoneNumber, 'status' => 1])
                ->where('created_at', 'like', date('Y-m-d') . '%')
                ->count('id');
            if ($todayQuantity > 20) {
                \DB::rollBack();
                self::$_errorMsg = '手机号已达到今日最大短信额度';
                return false;
            }
            
            $ipQuantity = SmsCode::where(['ip' => $ip, 'status' => 1])
                ->where('created_at', 'like', date('Y-m-d') . '%')
                ->count('id');
            if ($ipQuantity > 50) {
                \DB::rollBack();
                self::$_errorMsg = '发送失败,客户端已达到今日最大短信额度！';
                return false;
            }
            
            $srand = rand(100000,999999);
            
            $sms = new YunXinSmsService();
            $resp = $sms->sendSmsCode($phoneNumber,$srand);
            
            $is_success =  $resp['status'] == 'success' ? true : false;
            $model = new SmsCode();
            $model->mobile = $phoneNumber;
            $model->code = $srand;
            $model->response = $resp;
            $model->isuse = 0;
            $model->ip = $ip;
            $model->status = $is_success ? 1 : 0;
            $model->save();

            if (!$is_success) {
                self::$_errorMsg = $resp['msg'];
            }
            \DB::commit();
            return $is_success;
        } catch (\Exception $e) {
            \DB::rollBack();
            self::$_errorMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 验证短信验证码
     * @param $phoneNumber
     * @param $code
     * @return bool
     */
    public static function verify($phoneNumber, $code)
    {
        $model = SmsCode::where(['mobile' => $phoneNumber, 'code' => $code, 'status' => 1])
            ->orderByDesc('id')
            ->first();
        if (!$model || $model->isuse === 1 || (time() - strtotime($model->created_at)) > 600) {
            self::$_errorMsg = '短信验证码错误';
            return false;
        }
        $model->isuse = 1;
        $model->save();
        return true;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public static function getErrorMsg()
    {
        return isset(self::$_errorMsg) ? self::$_errorMsg : '系统异常';
    }
}
