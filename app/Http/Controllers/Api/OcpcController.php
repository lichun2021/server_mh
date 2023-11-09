<?php


namespace App\Http\Controllers\Api;

use App\BaiduChannel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * 文章
 * Class ArticleController
 * @package App\Http\Controllers\Api
 * @author 春风 <860646000@qq.com>
 */
class OcpcController extends Controller
{
    /**
     * ArticleController constructor.
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['uploadConvertData']]);
    }

    /**
     * 文章详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadConvertData()
    {
        $validator = Validator::make(request()->post(), [
            'conversionTypes' => ['required','array','min:1'],
        ], [
            'conversionTypes.required' => '缺少参数',
            'conversionTypes.array' => '类型错误',
            'conversionTypes.min' => '参数错误'
        ]);
        if ($validator->fails()) {
            return self::apiJson(501, $validator->errors()->first());
        }
        $conversionTypes = request()->post('conversionTypes');

        //\Log::error('①百度渠道回传数据', $conversionTypes);

        $channel = BaiduChannel::getChannel();
        if ($channel === null){
            return self::apiJson(500, '渠道不存在或被禁用');
        }

        $data = [
            'token' => $channel->token,
            'conversionTypes' => $conversionTypes
        ];

        //\Log::error('②百度渠道 '.$channel->name.' 回传完整数据', $data);
        $response = Http::post('https://ocpc.baidu.com/ocpcapi/api/uploadConvertData', $data);
        $res = $response->json();
        //\Log::error('③百度渠道 '.$channel->name.' 数据回传结果', $res);
        if($res['header']['status'] !== 0){
            return self::apiJson(502, $res['header']['errors']['message'] ?? $res['header']['desc']);
        }

        return self::apiJson();
    }
}
