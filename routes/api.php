<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//测试百度回传
Route::get('recall','Api\BaiduRecallController@recall');
//站点信息
Route::get('info', 'Api\PublicController@info');
//发送手机验证码
Route::post('send_sms', 'Api\AuthController@sendSms');
//注册
Route::post('register', 'Api\AuthController@register');
//登录
Route::post('login', 'Api\AuthController@login');
//手机短信验证码登陆
Route::post('sms_login', 'Api\AuthController@smsLogin');
//刷新Token
Route::post('auth/refresh', 'Api\AuthController@refresh');
//Steam
Route::get('steam/login', 'Api\AuthController@steamLogin')->name('auth.steam');
//Steam回调处理
Route::get('steam/handle', 'Api\AuthController@steamHandle')->name('auth.steam.handle');
//绑定Steam账户
Route::post('steam/bind_mobile', 'Api\AuthController@steamBindMobile');
//我的信息
Route::get('me', 'Api\AuthController@me');
//仓库
Route::get('storage', 'Api\UserController@storage');
//宝箱列表
Route::get('box/list', 'Api\BoxController@boxList');
//热门宝箱列表
Route::get('box/hot', 'Api\BoxController@hotBox');
//宝箱详情
Route::get('box/detail', 'Api\BoxController@detail');
//开箱
Route::post('box/open', 'Api\BoxController@open');
//开箱记录
Route::get('box/history', 'Api\BoxController@history');
Route::get('top_history', 'Api\BoxController@topHistory');
//提取
Route::post('extract', 'Api\BoxController@extract');
//兑换M豆
Route::post('cash', 'Api\BoxController@cash');
//赠送礼物
//Route::post('give', 'Api\UserController@give');
//修改Steam交易地址
Route::post('set_steam_url', 'Api\UserController@setSteamUrl');
//修改密码
Route::post('reset_password', 'Api\AuthController@resetPassword');
//修改推广码
//Route::post('set_invite_code', 'Api\UserController@setInvite_code');
//绑定推广人邀请码
Route::post('set_inviter', 'Api\UserController@setInviter');
//修改头像
Route::post('set_avatar', 'Api\UserController@setAvatar');
//修改用户名
Route::post('set_name', 'Api\UserController@setName');
//绑定手机号
Route::post('bind_mobile', 'Api\UserController@bindMobile');
//金豆列表
Route::get('beans', 'Api\BeanController@list');
//金豆充值订单详情
Route::get('bean/detail', 'Api\BeanController@detail');
//首冲
Route::get('beans/first', 'Api\PromoteController@first');
//金豆收支记录
Route::get('bean/change_records', 'Api\BeanController@changeRecords');
//收支明细类型
Route::get('bean/change_type', 'Api\BeanController@changeType');
//金豆充值
Route::post('recharge', 'Api\UserController@recharge');
//卡密充值
Route::post('card-recharge', 'Api\UserController@cardRecharge');
//金豆充值记录
Route::get('recharge_record', 'Api\UserController@rechargeRecord');
//房间列表
Route::get('rooms', 'Api\RoomController@list');
//个人参加记录
Route::get('user/rooms', 'Api\UserController@room');
//房间详情
Route::get('room/detail', 'Api\RoomController@detail');
//创建房间 应版本要求改为后台创建Roll房间
//Route::post('room/save', 'Api\RoomController@save');
//加入房间
Route::post('room/join', 'Api\RoomController@join');
//推广
Route::get('promote', 'Api\PromoteController@index');
//ImageBase64上传
Route::post('image/base64', 'Api\FileController@image_base64');
//排行榜
//Route::get('leaderboards', 'Api\BeanController@leaderboards');
//幸运开箱装备类型
Route::get('lucky/type', 'Api\LuckyOpenBoxController@type');
//幸运开箱装备类型列表
Route::get('lucky/list', 'Api\LuckyOpenBoxController@list');
//幸运开箱搜索装备列表
Route::get('lucky/search', 'Api\LuckyOpenBoxController@search');
//幸运开箱
Route::post('lucky/open', 'Api\LuckyOpenBoxController@open');
//幸运物品掉落记录
Route::get('lucky/history', 'Api\LuckyOpenBoxController@history');
//获取单个饰品详情
Route::get('skins/detail', 'Api\LuckyOpenBoxController@detail');
//加入盒子竞技频道
Route::post('arena/join-channel', 'Api\GameArenaController@JoinChannel');
//创盒子对战
Route::post('arena/create', 'Api\GameArenaController@create');
//对战详情
Route::get('arena/detail', 'Api\GameArenaController@detail');
//加入对战
Route::post('arena/join', 'Api\GameArenaController@join');
//对战列表
Route::get('arena/list', 'Api\GameArenaController@list');
//宝箱列表
Route::get('arena/box-list', 'Api\GameArenaController@boxList');
//历史记录
Route::get('arena/history', 'Api\GameArenaController@history');
//我的历史记录
Route::get('arena/me-history', 'Api\GameArenaController@meHistory');
//对战排行
Route::get('arena/ranking', 'Api\GameArenaController@ranking');
//欧皇排行
Route::get('arena/winRanking', 'Api\GameArenaController@WinRanking');
//幸运夺宝列表
Route::get('snatch', 'Api\SnatchController@list');
//夺宝详情
Route::get('snatch/detail', 'Api\SnatchController@detail');
//加入夺宝
Route::post('snatch/join', 'Api\SnatchController@join');
//饰品商店
Route::get('shop', 'Api\ShopController@index');
//饰品购买
//Route::post('shop/buy', 'Api\ShopController@buy');
//积分兑换
Route::post('shop/exchange', 'Api\ShopController@exchange');
//活动福利
Route::get('welfare', 'Api\WelfareController@index');
//活动福利开箱
Route::post('welfare/open', 'Api\WelfareController@open');
//红包活动
Route::get('red', 'Api\RedController@index');
//打开红包
Route::post('red/open', 'Api\RedController@open');
//装备合成
Route::post('synthesis', 'Api\SynthesisController@run');
//可合成列表
Route::get('synthesis/list', 'Api\SynthesisController@list');
//文章列表
Route::get('article', 'Api\ArticleController@index');
//文章详情
Route::get('article/detail', 'Api\ArticleController@detail');
//星星列表
Route::get('star_wars', 'Api\StarWarsController@index');
//星星开启
Route::post('star_wars/open', 'Api\StarWarsController@open');
//星星详情
Route::get('star_wars/detail', 'Api\StarWarsController@detail');
//星星重置
Route::post('star_wars/reset', 'Api\StarWarsController@reset');
//VIP列表
Route::get('vip/list', 'Api\VipController@list');
//VIP列表
Route::get('vip/lists', 'Api\VipController@lists');
//任务进度
Route::get('task', 'Api\TaskController@index');
//签到
Route::post('task/sign_up', 'Api\TaskController@signUp');
//领取任务奖励
Route::post('task/receive', 'Api\TaskController@receive');
//百度OCPC数据回传
Route::post('ocpc/uploadConvertData', 'Api\OcpcController@uploadConvertData');
//提取/充值统计
//新增接口
Route::get('user/info','Api\UserController@info');