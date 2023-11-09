<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*Route::get('/', function () {
    return view('welcome');
});*/

//Route::any('callback/recharge_bean', 'PayCallbackController@rechargeBean');
//Route::post('callback/dj_pay', 'PayCallbackController@djPay');
Route::get('/', 'IndexController@index');
Route::get('go', 'IndexController@go');
Route::get('to', 'IndexController@to');
Route::post('callback/alipay', 'PayCallbackController@alipay');
Route::post('callback/jiuja_pay', 'PayCallbackController@jiuJaPay');
Route::post('callback/umi_strong_pay', 'PayCallbackController@umiStrongPay');
Route::post('callback/fxkpay', 'PayCallbackController@fxPay');
Route::post('callback/vggstorepay', 'PayCallbackController@vggStorePay');
Route::post('callback/hnsqpay', 'PayCallbackController@hnsqPay');
Route::get('pay_qr_code', 'IndexController@payQrCode');
Route::post('zbt/notify', 'ZbtCallbackController@notify');
Route::post('bus/notify', 'BusCallbackController@notify');
Route::post('youpin/notify', 'YouPinCallbackController@notify');
