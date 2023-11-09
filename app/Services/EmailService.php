<?php
/**
 * 短信逻辑类
 *
 * @link https://market.cloud.tencent.com/products/5778#spec=8.00%E5%85%83/100%E6%AC%A1
 */

namespace App\Services;

use App\EmailCode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class EmailService
{

    /**
     * 发送验证码
     *
     * @param string $email 邮箱
     * @param string $code 验证码
     * @param string $title 邮箱标题
     * @return array
     */
    public static function send(string $email, string $code,string $title = '注册验证码')
    {
        //验证发送是否过于频繁（1小时内发送10条）
        $today_number = EmailCode::where(function ($query) use ($email) {
                            $query->where('email', $email)->orWhere('ip', request()->getClientIp());
                        })->where('created_at', '>', Carbon::now()->addhours(-1)->toDateTimeString())
                        ->count();
        if ($today_number >= 10) {
            return [
                'code' => 0,
                'message' => '发送过于频繁'
            ];
        }

        //保存短信
        $email_code = new EmailCode();
        $email_code->email = $email;
        $email_code->code = $code;
        $email_code->ip = request()->getClientIp();
        $email_code->is_use = 0;
        $email_code->save();

        Mail::raw('您的验证码为：'. $code .'，验证码5分钟内有效，请勿告诉其他人！', function ($message) use ($email,$title) {
            $message->subject($title);
            $message->to($email);
        });

        return [
            'code' => 1,
            'message' => '发送成功'
        ];
    }


    /**
     * 验证验证码
     *
     * @param string $email 手机号
     * @param string $code 验证码
     * @return array
     * @author 赵通通 1106935565@qq.com
     */
    public static function checkCode(string $email, string $code)
    {
        //验证短信是否正确
        $email_code = EmailCode::where('email', $email)
                            ->where('code', $code)
                            ->where('is_use', 0)
                            ->orderBy('id', 'DESC')->first();

        //判断时间差
        if (!$email_code || time() - strtotime($email_code->created_at) > 300) {
            return [
                'code' => 0,
                'message' => '验证码错误'
            ];
        }

        $email_code->is_use = 1;
        $email_code->save();

        return [
            'code' => 1,
            'message' => '验证码正确'
        ];
    }

}
