<?php

namespace App\Http\Controllers;

use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IndexController extends Controller
{
    public function index()
    {
        return self::apiJson(404, '访问页面不存在！');
    }

    /**
     * 生成二维码
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function payQrCode()
    {
        $qrCode = new QrCode(urldecode(request()->get('qr_code')));
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    public function go(Request $request)
    {
        $key = 'jiujia_pay_' . $request->key;
        $url = Cache::get($key);
        if ($url === null) {
            $key = 'hnsqpay_pay_' . $request->key;
            $url = Cache::get($key);
        } elseif ($url === null) {
            return '请求参数错误';
        }
        return redirect($url);
    }

    public function to(Request $request)
    {
        $key = 'vgg_store_pay_' . $request->key;
        $html = Cache::get($key);
        if ($html === null) {
            return '订单超时，请重新下单！';
        }
        return $html;
    }
}
