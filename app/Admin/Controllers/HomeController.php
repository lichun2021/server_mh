<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Services\YouPinService;
use App\Services\V5ItemService;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets;
use App\Services\ZbtService;
use App\BoxRecord;
use App\BeanRecord;
use App\User;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        $youPinInfo = YouPinService::getAssetsInfo();
        $v5ItemInfo = V5ItemService::checkMerchantBalance();
        $delivery = BoxRecord::query()->where('status', 4)->count();
        $recharge = BeanRecord::query()->where(['status' => 1, 'is_pay_api' => 1])->sum('price');
        $recharge_m = BeanRecord::query()->where(['status' => 1, 'is_pay_api' => 1])->sum('bean');
        $yday = date("Y-m-d",strtotime("-1 day")).'%';
        $yesterdayBean = BeanRecord::where(['status' => 1, 'is_pay_api' => 1])->where('created_at', 'LIKE', $yday)->sum('bean');
        $yesterdayRmb = BeanRecord::where(['status' => 1, 'is_pay_api' => 1])->where('created_at', 'LIKE', $yday)->sum('price');
        $todayBean = BeanRecord::where(['status' => 1, 'is_pay_api' => 1])->where('created_at', 'LIKE', date('Y-m-d') . '%')->sum('bean');
        $todayRmb = BeanRecord::where(['status' => 1, 'is_pay_api' => 1])->where('created_at', 'LIKE', date('Y-m-d') . '%')->sum('price');
        $user = User::where('mobile','>','12999999999')->count();
        $userTodayNum = User::where('mobile','>','12999999999')->where('created_at', 'LIKE', date('Y-m-d') . '%')->count('id');
        return $content
            ->title(config('admin.title'))
            ->description('数据看板')
            ->row(function (Row $row) use ($youPinInfo, $v5ItemInfo, $delivery, $recharge, $recharge_m, $user, $yesterdayBean, $yesterdayRmb, $todayBean, $todayRmb, $userTodayNum) {
                $row->column(3, new Widgets\InfoBox('总用户', 'users', 'aqua', '/' . config('admin.route.prefix') . '/users', $user));
                $row->column(3, new Widgets\InfoBox('总充值 '.getConfig('bean_name').'：' . $recharge_m, 'cny', 'red', '/' . config('admin.route.prefix') . '/bean-records', '¥' . $recharge));
                $row->column(3, new Widgets\InfoBox('待处理提货申请', 'shopping-cart', 'green', '/' . config('admin.route.prefix') . '/delivery', $delivery));
                if ($v5ItemInfo['code'] === 0) {
                    $row->column(3, new Widgets\InfoBox('V5Item余额', 'vine', 'orange', '#', '¥' . round($v5ItemInfo['data']['balance'], 2)));
                } else {
                    $row->column(3, new Widgets\InfoBox($v5ItemInfo['msg'], 'vine', 'orange', '#', 'Api接口错误'));
                }
                if ($youPinInfo['code'] === 0) {
                    $row->column(3, new Widgets\InfoBox('有品余额', 'yahoo', 'navy', '#', '¥' . round($youPinInfo['data']['amount'], 2)));
                } else {
                    $row->column(3, new Widgets\InfoBox($youPinInfo['msg'], 'yahoo', 'navy', '#', 'Api接口错误'));
                }
                $row->column(3, new Widgets\InfoBox('今日注册用户数', 'calendar-minus-o', 'lime', '/' . config('admin.route.prefix') . '/users', $userTodayNum));
                $row->column(3, new Widgets\InfoBox('今日充值人民币：'.$todayRmb, 'calendar-check-o', 'blue', '/' . config('admin.route.prefix') . '/bean-records', $todayBean));
                $row->column(3, new Widgets\InfoBox('昨日充值人民币：'.$yesterdayRmb, 'paypal', 'purple', '/' . config('admin.route.prefix') . '/bean-records', $yesterdayBean));
                //充值
                $recharge = BeanRecord::query()->select([\DB::raw("DATE_FORMAT( created_at, '%Y-%m-%d' ) AS date"),\DB::raw("SUM(bean) AS bean")])
                    ->where(['status' => 1,'is_pay_api' => 1])
                    ->groupBy('date')
                    ->orderBy('date','desc')
                    ->limit(10)
                    ->get()
                    ->toArray();
                sort($recharge);
                $recharge_l = [];
                $recharge_d = [];
                foreach ($recharge as $item){
                    $recharge_l[] = $item['date'];
                    $recharge_d[] = $item['bean'];
                }
                $row->column(6, new Widgets\Box('10天运营趋势',view('admin.chartjs',[
                    'recharge_l' => json_encode($recharge_l),
                    'recharge_d' => json_encode($recharge_d)
                ])));
                //
                $zbtRes = ZbtService::open_development_info();
                if ($zbtRes['success']){
                    $data = $zbtRes['data'];
                    $status = [
                        'WAITING' => '待审核',
                        'NORMAL' => '正常',
                        'FORBIDDEN' => '封禁',
                        'LOGOUT' => '登出',
                        'REJECT' => '拒绝',
                        'FAILED' => '失败',
                    ];
                    $rows = [
                        ['帐号余额', $data['balance']],
                        ['余额预警值', $data['balanceLack']],
                        ['appKey', $data['appKey']],
                        ['appSecret', $data['appSecret']],
                        ['白名单Ip', $data['grantIpList']],
                        ['回调地址', $data['callbackUrl']],
                        ['当前状态', $status[$data['status']]],
                    ];
                    $row->column(3, new Widgets\Box('ZBT信息',new Widgets\Table([], $rows)));
                }
            });
    }
}
