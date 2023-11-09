<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SubMailService
{
    /**
     * 发送短信验证码
     * @param $mobile
     * @param $code
     * @return array|mixed
     */
    public static function sendSms($mobile, $code)
    {
        $data = [
            'appid' => config('sub-mail-sms.appid'),
            'to' => $mobile,
            'project' => config('sub-mail-sms.template'),
            'vars' => json_encode(['code' => $code]),
            'signature' => config('sub-mail-sms.app_key')
        ];

        $response = Http::post('https://api-v4.mysubmail.com/sms/xsend', $data);
        $res = $response->json();
        return $res;
    }

    /**
     * 发送通知短信
     * @param $mobile
     * @return array|mixed
     */
    public static function sendNotice($mobile)
    {
        $data = [
            'appid' => config('sub-mail-sms.appid'),
            'to' => $mobile,
            'project' => config('sub-mail-sms.notice_template'),
            'signature' => config('sub-mail-sms.app_key')
        ];
        $response = Http::post('https://api-v4.mysubmail.com/sms/xsend', $data);
        $res = $response->json();
        return $res;
    }
}
