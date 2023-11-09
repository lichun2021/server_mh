<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaiduRecallController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['recall']]);
    }
    const BAIDU_OCPC_URL = 'https://ocpc.baidu.com/ocpcapi/api/uploadConvertData';
    const RETRY_TIMES = 3;

    /**
     * @param $token
     * @param $conversionTypes
     * @return bool 发送成功返回true，失败返回false
     */
    public function sendConvertData($token, $conversionTypes) {
        $reqData = array('token' => $token, 'conversionTypes' => $conversionTypes);
        $reqData = json_encode($reqData);
        // 发送完整的请求数据
        // do some log
        print_r('req data: ' . $reqData . "\n");
        // 向百度发送数据
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, self::BAIDU_OCPC_URL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($reqData)
            )
        );
        // 添加重试，重试次数为3
        for ($i = 0; $i < self::RETRY_TIMES; $i++) {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode === 200) {
                // 打印返回结果
                // do some log
                print_r('retry times: ' . $i . ' res: ' . $response . "\n");
                $res = json_decode($response, true);
                // status为4，代表服务端异常，可添加重试
                $status = $res['header']['status'];
                if ($status !== 4) {
                    curl_close($ch);
                    return $status === 0;
                }
            }
        }
        curl_close($ch);
        return false;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function recall(){
        $token = '62oGtEbz4p86E76aNwFCiY6GiY4o0YgR@ELImqO2GP4M2idBNSq6LquGSP9PcK8YX';
        $cv = array(
            'logidUrl' => 'http://t8csgo.xingxiankj.top?bd_vid=uANBIyIxUhNLgvw-I-tknHfYnWRkg1cLg1Dvrjc4n1cLPW61PHn', // 您的落地页url
            'newType' => 3 // 转化类型请按实际情况填写
        );
        $conversionTypes = array($cv);
        $demo = new BaiduRecallController();
        $demo->sendConvertData($token, $conversionTypes);
        return self::apiJson();
    }

}
