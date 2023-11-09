<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/30 15:43
 */

namespace App\Http\Controllers\Api;

use App\Banner;
use App\Http\Controllers\Controller;

class PublicController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['info']]);
    }

    /**
     * 公用站点信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function info()
    {
        $bannerCacheKey = Banner::$fields['cacheKey'];
        $banners = \Cache::get($bannerCacheKey);
        if ($banners === null) {
            $banners = Banner::select(['title', 'image', 'href'])
                ->where('status', 1)
                ->orderBy('sort')
                ->orderByDesc('id')
                ->get()
                ->toArray();
            \Cache::put($bannerCacheKey, $banners);
        }
        $data = [
            'site_name' => getConfig('site_name'),
            'site_bulletin' => getConfig('site_bulletin'),
            'qq_group_qr_code' => getConfig('qq_group_qr_code'),
            'qq_group_number' => getConfig('qq_group_number'),
            'website_icp' => getConfig('website_icp'),
            'banners' => $banners,
            //'user_number' => User::count('id'),
            //'open_box_number' => BoxRecord::count('id'),
        ];
        return self::apiJson(200, 'ok', $data);
    }
}
