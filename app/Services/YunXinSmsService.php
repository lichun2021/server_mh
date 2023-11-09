<?php

namespace App\Services;

class YunXinSmsService
{
    public $AppKey;                //开发者平台分配的AppKey
    public $AppSecret;             //开发者平台分配的AppSecret,可刷新
    public $Nonce;                    //随机数（最大长度128个字符）
    public $CurTime;                //当前UTC时间戳，从1970年1月1日0点0 分0 秒开始到现在的秒数(String)
    public $CheckSum;                //SHA1(AppSecret + Nonce + CurTime),三个参数拼接的字符串，进行SHA1哈希计算，转化成16进制字符(String，小写)
    const HEX_DIGITS = "0123456789abcdef";

    /**
     * 参数初始化
     */
    public function __construct()
    {
        $this->AppKey = config('yunxinsms.apiKey');
        $this->AppSecret = config('yunxinsms.appSecret');
    }

    /**
     * API checksum校验生成
     * @param void
     * @return $CheckSum(对象私有属性)
     */
    public function checkSumBuilder()
    {
        //此部分生成随机字符串
        $hex_digits = self::HEX_DIGITS;
        $this->Nonce;
        for ($i = 0; $i < 128; $i++) {            //随机字符串最大128个字符，也可以小于该数
            $this->Nonce .= $hex_digits[rand(0, 15)];
        }
        $this->CurTime = (string)time();    //当前时间戳，以秒为单位

        $join_string = $this->AppSecret . $this->Nonce . $this->CurTime;
        $this->CheckSum = sha1($join_string);
        //print_r($this->CheckSum);
    }

    /**
     * 将json字符串转化成php数组
     * @param  $json_str
     * @return $json_arr
     */
    public function json_to_array($json_str)
    {

        if (is_array($json_str) || is_object($json_str)) {
            $json_str = $json_str;
        } else if (is_null(json_decode($json_str))) {
            $json_str = $json_str;
        } else {
            $json_str = strval($json_str);
            $json_str = json_decode($json_str, true);
        }
        $json_arr = array();
        foreach ($json_str as $k => $w) {
            if (is_object($w)) {
                $json_arr[$k] = $this->json_to_array($w); //判断类型是不是object
            } else if (is_array($w)) {
                $json_arr[$k] = $this->json_to_array($w);
            } else {
                $json_arr[$k] = $w;
            }
        }
        return $json_arr;
    }

    /**
     * 使用CURL方式发送post请求
     * @param  $url [请求地址]
     * @param  $data [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postDataCurl($url, $data)
    {
        $this->checkSumBuilder();       //发送请求前需先生成checkSum

        $timeout = 10;
        $http_header = array(
            'AppKey:' . $this->AppKey,
            'Nonce:' . $this->Nonce,
            'CurTime:' . $this->CurTime,
            'CheckSum:' . $this->CheckSum,
            'Content-Type:application/x-www-form-urlencoded;charset=utf-8'
        );

        $postdataArray = array();
        foreach ($data as $key => $value) {
            array_push($postdataArray, $key . '=' . urlencode($value));
        }
        $postdata = join('&', $postdataArray);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (false === $result) {
            $result = curl_errno($ch);
        }
        curl_close($ch);
        return $this->json_to_array($result);
    }

    /**
     * 发送短信验证码
     * @param  $mobile [目标手机号]
     */
    public function sendSmsCode($mobile,$str)
    {
        $url = 'https://api-v4.mysubmail.com/sms/xsend';
        // $url = 'https://api.netease.im/sms/sendcode.action';
        
        // $data = array(
        //     'templateid' => config('yunxinsms.templateId'),
        //     'mobile' => $mobile,
        //     'codeLen' => 6
        // );
        $data = array(
            'appid' =>  config('yunxinsms.apiKey'), 
            'to'=>$mobile,
            'project' =>  config('yunxinsms.templateId'),
            'vars' => '{"code":'.$str.'}',
            'signature' => config('yunxinsms.appSecret')
        );
        return $this->postDataCurl($url, $data);
    }
    
    /**
     * 发送短信通知
     * @param  array $mobiles 手机号
     */
    public function sendSmsNotice($mobiles)
    {
        $url = 'https://api.netease.im/sms/sendtemplate.action';
        $data = array(
            'templateid' => config('yunxinsms.notice_templateId'),
            'mobiles' => json_encode($mobiles),
        );

        return $this->postDataCurl($url, $data);
    }
}
