<?php

namespace App\Http\Controllers\Api;

use App\BeanChangeRecord;
use App\User;
use App\LoginIpLog;
use App\BaiduChannel;
use App\Services\SmsService;
use App\Http\Controllers\Controller;
use App\Services\SensitiveWordsService;
use App\UserRewardLog;
use Invisnik\LaravelSteamAuth\SteamAuth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * The SteamAuth instance.
     *
     * @var SteamAuth
     */
    protected $steam;

    /**
     * The redirect URL.
     *
     * @var string
     */
    protected $redirectURL = '/';

    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct(SteamAuth $steam)
    {
        $this->middleware('jwt.auth', ['except' => ['register', 'login', 'resetPassword', 'sendSms', 'steamLogin', 'steamHandle', 'steamBindMobile', 'smsLogin']]);
        $this->steam = $steam;
    }

    /**
     * 获取短信验证码
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSms()
    {
        $validator = Validator::make(request()->post(), [
            'mobile' => 'required|regex:/^1[3456789]{1}\d{9}$/',
            'is_use' => 'required|in:1,2,3'
        ], [
            'mobile.required' => '请输入手机号',
            'mobile.regex' => '手机号不正确',
            'is_use.required' => '请输入用途',
            'is_use.in' => '用途错误'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        $mobile = request()->mobile;
        $is_use = request()->is_use;
        $user = User::where('mobile', $mobile)->first();

        if ($is_use == 1 && $user) {
            return self::apiJson(500, '手机号已被注册');
        } elseif (in_array($is_use, [2, 3]) && !$user) {
            return self::apiJson(500, '手机号尚未注册');
        }
                
        if (!SmsService::sendSms(request()->mobile)) {
            return self::apiJson(500, SmsService::getErrorMsg());
        }

        return self::apiJson(200, '发送成功');
    }

    /**
     * 注册
     *
     * @param string $mobile 手机号
     * @param string $pass 密码
     * @param string $name 用户名
     * @param string $verify 验证码
     * @param string $invite 邀请码
     * @return \Illuminate\Http\JsonResponse
     */
    public function register()
    {
        $data = request()->post();
        $rules = [
            'mobile' => 'required|regex:/^1[3456789]{1}\d{9}$/|unique:users',
            'pass' => 'required|min:6',
            'verify' => 'required'
        ];
        if (array_key_exists('name', $data)) {
            $rules['name'] = 'required|min:2|max:10|unique:users';
        }
        $validator = Validator::make($data, $rules, [
            'mobile.required' => '请输入手机号码',
            'mobile.regex' => '手机号不正确',
            'mobile.unique' => '手机号已被注册',
            'pass.required' => '请输入密码',
            'pass.min' => '密码不能小于6个字符',
            'name.required' => '请输入用户名',
            'name.min' => '用户名不能小于2个字符',
            'name.max' => '用户名不能超过10个字符',
            'name.unique' => '用户名已被占用',
            'verify.required' => '请输入验证码'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        $mobile = request()->post('mobile');
        //昵称敏感词过滤
        if (array_key_exists('name', $data)) {
            $is_word = SensitiveWordsService::getBadWord(request()->post('name'));
            if ($is_word) {
                return self::apiJson(500, '用户名包含敏感词 “' . implode('、', $is_word) . '” 请修改！');
            }
            $name = request()->post('name');
        } else {
            $name = '用户' . substr($mobile, -4);
            if (User::where('name', $name)->exists()) {
                $name = '用户' . substr($mobile, -4) . '_' . random(2, true);
            }
        }

        if (!SmsService::verify($mobile, request()->post('verify'))) {
            return self::apiJson(500, SmsService::getErrorMsg());
        }

        $user = new User();

        $user->mobile = request()->post('mobile');
        $user->name = $name;
        $user->password = password_hash(request()->post('pass'), PASSWORD_DEFAULT);

        //邀请人
        if (request()->post('invite')) {
            $inviter = User::where('invite_code', request()->post('invite'))->first();
            if ($inviter) {
                $user->inviter_id = $inviter->id;
            } else {
                return self::apiJson(500, '邀请码错误');
            }
        }

        $user->invite_code = md5($user->email . $user->name . $user->password);
        if ($user->save()) {
            $channel = BaiduChannel::getChannel();
            if ($channel) {
                $user->baidu_channel_id = $channel->id;
            }
            $bean = randFloat(8, 10);
            $invite_code = 'A' . getInviteCode($user->id);
            $user->invite_code = $invite_code;
            $default_avatar = ['default_avatar/1.jpg', 'default_avatar/2.jpg', 'default_avatar/3.jpg', 'default_avatar/4.jpg', 'default_avatar/5.jpg', 'default_avatar/6.jpg', 'default_avatar/7.jpg', 'default_avatar/8.jpg', 'default_avatar/9.jpg', 'default_avatar/10.jpg', 'default_avatar/11.jpg', 'default_avatar/12.jpg', 'default_avatar/13.jpg', 'default_avatar/14.jpg', 'default_avatar/15.jpg', 'default_avatar/16.jpg', 'default_avatar/17.jpg', 'default_avatar/18.jpg', 'default_avatar/19.jpg', 'default_avatar/20.jpg', 'default_avatar/21.jpg', 'default_avatar/22.jpg', 'default_avatar/23.jpg', 'default_avatar/24.jpg', 'default_avatar/25.jpg', 'default_avatar/26.jpg', 'default_avatar/27.jpg', 'default_avatar/28.jpg', 'default_avatar/29.jpg', 'default_avatar/30.jpg'];
            $key = array_rand($default_avatar, 1);
            $user->avatar = $default_avatar[$key];
            $user->bean = $bean;
            $user->save();

            BeanChangeRecord::add(1, 23, $bean, $user->id);
            //写奖励记录
            $log = new UserRewardLog();
            $log->user_id = $user->id;
            $log->type = 13;
            $log->next_user_id = null;
            $log->bean = $bean;
            $log->save();
            //记录登录Ip
            $login_log = new LoginIpLog();
            $login_log->user_id = $user->id;
            $login_log->ip = request()->getClientIp();
            $login_log->save();


            return self::apiJson(200, 'ok', [
                'access_token' => auth('api')->login($user),
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);
        }

        return self::apiJson(500, '注册失败');
    }

    /**
     * 登录
     *
     * @param string $mobile 手机号
     * @param string $pass 密码
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $validator = Validator::make(request()->post(), [
            'mobile' => 'required|regex:/^1[23456789]{1}\d{9}$/|exists:users',
            'pass' => 'required',
        ], [
            'mobile.required' => '请输入手机号',
            'mobile.regex' => '手机号不正确',
            'mobile.exists' => '该手机号未注册',
            'pass.required' => '请输入密码'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        $user = User::where('mobile', request()->post('mobile'))->first();

        //账号被禁用
        if ($user->status === 0) {
            return self::apiJson(500, '您的账号因违反平台规定被封禁！');
        }

        /*if (!$user) {
            return self::apiJson(500,'该账号未注册！');
        }*/

        if (!password_verify(request()->post('pass'), $user->password)) {
            return self::apiJson(500, '密码错误！');
        }
        //记录登录Ip
        $login_log = new LoginIpLog();
        $login_log->user_id = $user->id;
        $login_log->ip = request()->getClientIp();
        $login_log->save();

        return self::apiJson(200, 'ok', [
            'access_token' => auth('api')->login($user),
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * 登录
     *
     * @param string $mobile 邮箱
     * @param string $pass 密码
     * @return \Illuminate\Http\JsonResponse
     */
    public function smsLogin()
    {
        $validator = Validator::make(request()->post(), [
            'mobile' => 'required|regex:/^1[3456789]{1}\d{9}$/|exists:users,mobile',
            'verify' => 'required',
        ], [
            'mobile.required' => '请输入手机号',
            'mobile.regex' => '手机号不正确',
            'mobile.exists' => '手机号未注册',
            'verify.required' => '请输入短信验证码'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        if (!SmsService::verify(request()->post('mobile'), request()->post('verify'))) {

            return self::apiJson(500, SmsService::getErrorMsg());
        }

        $user = User::where('mobile', request()->post('mobile'))->first();

        //记录登录Ip
        $login_log = new LoginIpLog();
        $login_log->user_id = $user->id;
        $login_log->ip = request()->getClientIp();
        $login_log->save();

        return self::apiJson(200, 'ok', [
            'access_token' => auth('api')->login($user),
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * 更新密码
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword()
    {
        $validator = Validator::make(request()->post(), [
            'mobile' => 'required|regex:/^1[3456789]{1}\d{9}$/|exists:users',
            'password' => 'required|min:6',
            'verify' => 'required',
        ], [
            'mobile.required' => '请输入手机号',
            'mobile.email' => '手机号不正确',
            'mobile.exists' => '手机号未注册',
            'password.required' => '请输入密码',
            'password.min' => '密码最少6个字符',
            'verify.required' => '请输入验证码',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        if (!SmsService::verify(request()->post('mobile'), request()->post('verify'))) {
            return self::apiJson(500, SmsService::getErrorMsg());
        }

        $user = User::where('mobile', request()->post('mobile'))->first();
        $user->password = password_hash(request()->post('password'), PASSWORD_DEFAULT);
        if ($user->save()) {
            return self::apiJson(200, '密码修改成功！');
        }

        return self::apiJson(500, '密码修改失败！');
    }

    /**
     * Steam登录
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function steamLogin()
    {
        return redirect($this->steam->getAuthUrl());
    }

    /**
     * Steam 登录验证
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function steamHandle()
    {
        try {
            if ($this->steam->validate()) {
                $info = $this->steam->getUserInfo();

                if (!is_null($info)) {
                    $user = $this->findOrNewUser($info);
                    if ($user === false) {
                        return self::apiJson(422, 'NoUser', base64_encode(openssl_encrypt(json_encode($info, 256), 'AES-128-ECB', '97skinsCnSteamData', true)));
                    }
                    //账户禁用
                    if (!empty($user) && $user->status === 0) {
                        return self::apiJson(500, '您的账号因违反平台规定被封禁！');
                    }

                    //记录登录Ip
                    $login_log = new LoginIpLog();
                    $login_log->user_id = $user->id;
                    $login_log->ip = request()->getClientIp();
                    $login_log->save();

                    return self::apiJson(200, 'ok', [
                        'access_token' => auth('api')->login($user),
                        'token_type' => 'bearer',
                        'expires_in' => auth('api')->factory()->getTTL() * 60
                    ]);

                }
            }
            return self::apiJson(500, '数据验证失败！');
        } catch (\Exception $e) {
            \Log::error('Steam登录认证异常', ['File' => $e->getFile(), 'Line' => $e->getLine(), 'Message' => $e->getMessage()]);
            return self::apiJson(500, '系统网络错误认证失败！');
        }
    }

    /**
     * 绑定Steam
     * @return \Illuminate\Http\JsonResponse
     */
    public function steamBindMobile()
    {
        $validator = Validator::make(request()->post(), [
            'mobile' => 'required|regex:/^1[3456789]{1}\d{9}$/',
            'verify' => 'required|integer|min:1|max:999999',
            'steam_data' => 'required|string|min:512',
        ], [
            'mobile.required' => '请输入手机号码',
            'mobile.regex' => '手机号不正确',
            'verify.required' => '请输入验证码',
            'verify.integer' => '验证码错误',
            'verify.min' => '验证码错误',
            'verify.max' => '验证码错误',
            'steam_data.required' => '请输入Steam签名字符串',
            'steam_data.string' => 'Steam签名错误',
            'steam_data.min' => 'Steam签名错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        try {
            //验证Steam字符串
            $steamData = json_decode(openssl_decrypt(base64_decode(request()->post('steam_data')), 'AES-128-ECB', '97skinsCnSteamData', true), true);
            $mobile = request()->post('mobile');
            if (empty($steamData) || !is_array($steamData)) {
                return self::apiJson(500, 'Steam签名字符串错误！');
            }
            //验证手机验证码
            if (!SmsService::verify($mobile, request()->post('verify'))) {
                return self::apiJson(500, SmsService::getErrorMsg());
            }

            $user = User::where(['mobile' => $mobile])->first();
            //账户禁用
            if ($user->status === 0) {
                return self::apiJson(500, '您的账号因违反平台规定被封禁！');
            }

            if ($user === null) {
                $user = new User();
                $user->name = $steamData['personaname'];
                $user->mobile = $mobile;
                $user->avatar = $steamData['avatarfull'];
                $user->steam_id = $steamData['steamID64'];
                $user->password = password_hash('92skins' . rand(100000000, 999999999), PASSWORD_DEFAULT);
                $user->invite_code = md5($steamData['steamID64']);
                $user->save();

                $channel = BaiduChannel::getChannel();
                if ($channel) {
                    $user->baidu_channel_id = $channel->id;
                }
                $invite_code = 'K' . getInviteCode($user->id);
                $user->invite_code = $invite_code;
                $user->save();
            } else {
                $user->steam_id = $steamData['steamID64'];
                $user->save();
            }

            return self::apiJson(200, 'ok', [
                'access_token' => auth('api')->login($user),
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);
        } catch (\Exception $e) {
            return self::apiJson(500, $e->getMessage());
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = User::with('inviter:id,name,invite_code')->where(['id' => auth('api')->id()])
            ->select(['id', 'name', 'mobile', 'email', 'invite_code', 'avatar', 'inviter_id', 'bean', 'integral', 'steam_url', 'steam_id', 'promotion_level', 'vip_level', 'is_recharge', 'total_recharge', 'created_at', 'updated_at'])
            ->first()
            ->append('charge-rebate', 'personal-total', 'promotion-total');
        return self::apiJson(200, 'ok', $user);
    }

    /**
     * Refresh a token.
     * 刷新token，如果开启黑名单，以前的token便会失效。
     * 值得注意的是用上面的getToken再获取一次Token并不算做刷新，两次获得的Token是并行的，即两个都可用。
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return self::apiJson(200, 'ok', [
            'access_token' => auth('api')->refresh(),
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * 根据SteamId获取用户资料
     * @param $info
     * @return bool
     */
    protected function findOrNewUser($info)
    {
        $user = User::where('steam_id', $info->steamID64)->first();

        if (!is_null($user)) {
            return $user;
        }
        return false;
    }
}
