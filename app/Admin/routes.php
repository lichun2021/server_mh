<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {
    //待删除
    $router->resource('award-levels', AwardLevelController::class);
    $router->resource('box-awards', BoxAwardController::class);
    $router->resource('award-types', AwardTypeController::class);
    $router->resource('type-awards', TypeAwardController::class);
    //
    $router->get('/', 'HomeController@index')->name('home');
    $router->get('setting', 'ConfigController@setting');
    $router->resource('users', UserController::class);
    $router->resource('boxs', BoxController::class);
    $router->resource('box-records', BoxRecordController::class);
    $router->resource('bean-records', BeanRecordController::class);
    $router->resource('beans', BeanController::class);
    $router->resource('rooms', RoomController::class);
    #$router->resource('room-award', RoomAwardController::class);
    $router->resource('room-users', RoomUserController::class);
    $router->resource('room-jackpot', RoomJackpotController::class);
    $router->resource('room-jackpots-list', RoomJackpotListController::class);
    $router->resource('steam-items', SteamItemController::class);
    $router->resource('award-levels', AwardLevelController::class);
    //新饰品管理
    $router->resource('skins', SkinsController::class);
    $router->resource('skins-lv', SkinsLvController::class);
    $router->resource('skins-type', SkinsTypeController::class);
    //新饰品管理结束
    //
    $router->resource('box-contains', BoxContainController::class);
    //
    $router->resource('promotion-levels', PromotionLevelController::class);
    //功能已去除 $router->resource('recharge-rebates', RechargeRebateController::class);
    $router->resource('first-recharge-offers', FirstRechargeOfferController::class);
    $router->resource('user-reward-logs', RewardLogController::class);
    $router->resource('lucky-skins', LuckySkinsController::class);
    $router->resource('lucky-box-records', LuckyBoxRecordController::class);
    $router->resource('delivery', DeliveryController::class);
    $router->resource('zbt', ZbtController::class);
    $router->resource('youpin', YouPinController::class);
    $router->resource('delivery-records', DeliveryRecordController::class);
    $router->resource('card', CardController::class);
    $router->resource('game-arenas', GameArenaController::class);
    $router->resource('game-box', GameBoxController ::class);
    $router->resource('game-arena-users', GameArenaUserController::class);
    $router->resource('game-awards', GameAwardController ::class);
    $router->resource('game-award-records', GameAwardRecordController::class);
    $router->resource('game-arena-bot', GameArenaBotController::class);
    $router->resource('snatches', SnatchController::class);
    $router->resource('shop', ShopController::class);
    $router->resource('welfares', WelfareController::class);
    $router->resource('welfare-cdk', WelfareCdkController::class);
    $router->resource('welfare-records', WelfareRecordController::class);
    $router->resource('reds', RedController::class);
    $router->resource('red-keys', RedKeyController::class);
    $router->resource('red-records', RedRecordController::class);
    $router->resource('login-ip-logs', LoginIpLogController::class);
    $router->resource('synthesis', SynthesisController::class);
    $router->resource('synthe-records', SyntheRecordController::class);
    $router->resource('sensitive-words', SensitiveWordController::class);
    $router->resource('box-cates', BoxCateController::class);
    $router->resource('articles', ArticleController::class);
    $router->resource('vips', VipController::class);
    $router->resource('bean-change-records', BeanChangeRecordController::class);
    $router->resource('rankings', GameArenaRankingController::class);
    //红星轮盘
    $router->resource('stars-list', StarsListController::class);
    $router->resource('stars-contain', StarsContainController::class);
    $router->resource('stars-records', StarsRecordController::class);
    //banner
    $router->resource('banners', BannerController::class);
    //TagList
    $router->resource('user-tag-list', UserTagListController::class);
    $router->resource('baidu-channels', BaiduChannelController::class);
    //支付宝支付池
    $router->resource('alipays', AlipayController::class);
    //微信支付池
    $router->resource('wechatpays', WechatpayController::class);
    //Api
    $router->get('api/skins', 'ApiController@skins');
    $router->get('api/snatch-skins', 'ApiController@snatchSkins');
    $router->get('api/users', 'ApiController@users');
    $router->get('api/room/awards', 'ApiController@roomAwards');
    //File
    $router->post('file/image', 'FileController@image');
});
