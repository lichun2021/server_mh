<?php

namespace App\Http\Controllers;

use App\BeanRecord;
use App\Services\AlipayService;
use App\Services\FxPayService;
use App\Services\HnsqPayService;
use App\Services\JiuJaPayService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\RechargeService;
use App\Services\VggStorePayService;
use App\Services\UmiStrongPayService;

class PayCallbackController extends Controller
{

    /**
     * 支付宝充值到账
     * @param $code integer 订单号
     * @param $total_fee int 订单总金额
     * @throws \Throwable
     */
    public function alipay()
    {
        $data = request()->post();
        Log::info('支付宝回调来了', $data);
        if (empty($data['trade_status']) || empty($data['app_id']) || $data['app_id'] != config('alipay.app_id')) {
            Log::warning('支付宝回调非法调用', $data);
            return 'failure';
        }
        if ($data['trade_status'] != 'TRADE_SUCCESS') {
            return 'success';
        }
        //签名验证
        if (AlipayService::verifyNotify($data)) {
            DB::transaction(function () use ($data) {
                $bean_record = BeanRecord::where(['code' => $data['out_trade_no']])->lockForUpdate()->first();
                if (!$bean_record) {
                    throw new \Exception("记录不存在：" . $data['out_trade_no']);
                }
                if ($bean_record->status != 0) {
                    throw new \Exception("重复操作：" . $data['out_trade_no'], -1);
                }
                //检查金额
                if (bccomp($bean_record->price, $data['total_amount'], 2) !== 0) {
                    throw new \Exception("价格不相等：" . $data['out_trade_no'], -1);
                }
                //入账
                RechargeService::run($bean_record->user_id, $bean_record->bean, false);
                //回调成功
                $bean_record->trade_no = $data['trade_no'];
                $bean_record->status = 1;
                $bean_record->is_pay_api = 1;
                $bean_record->save();
            });
            return 'success';
        } else {
            return 'failure';
        }
    }

    /**
     * 九嘉支付回调通知
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|string
     * @throws \Throwable
     */
    public function jiuJaPay()
    {
        $data = request()->post();
        //Log::info('九嘉支付回调来了', $data);
        if (!isset($data['member_id'])
            || !isset($data['total_fee'])
            || !isset($data['result_code'])
            || empty($data['trade_no'])
            || empty($data['out_trade_no'])
            || empty($data['sign'])
            || $data['member_id'] != config('jiujapay.member_id')
        ) {
            return response('fail', 401);
        }
        $sign_flag = JiuJaPayService::verifySign();
        if ($sign_flag) {
            //签名验证成功
            try {
                DB::transaction(function () use ($data) {
                    $bean_record = BeanRecord::where(['code' => $data['trade_no']])->lockForUpdate()->first();
                    if (!$bean_record) {
                        throw new \Exception("记录不存在：" . $data['trade_no'], -1);
                    }
                    if ($bean_record->status != 0) {
                        throw new \Exception("重复操作：" . $data['trade_no'], -1);
                    }
                    //检查金额
                    if (bccomp($bean_record->price, $data['total_fee'], 2) !== 0) {
                        throw new \Exception("价格不相等：" . $data['total_fee'], -1);
                    }
                    //入账
                    RechargeService::run($bean_record->user_id, $bean_record->bean, false);
                    //回调成功
                    $bean_record->trade_no = $data['out_trade_no'];
                    $bean_record->status = 1;
                    $bean_record->is_pay_api = 1;
                    $bean_record->save();
                });
                return 'success';
            } catch (\Exception $e) {
                $data['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
                Log::error('九嘉支付回调出错', $data);
                if ($e->getCode() == -1) {
                    $message = 'fail';
                } else {
                    $message = 'fail';
                }
                return response($message, 400);
            }
        }
        return response('fail', 400);
    }

    /**
     * 富信卡支付回调通知
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|string
     * @throws \Throwable
     */
    public function fxPay()
    {
        $data = request()->post();
        Log::info('富信支付回调来了', $data);
        if (!isset($data['sign'])
            || !isset($data['sdorderno'])
            || !isset($data['goods_id'])
        ) {
            return 0;
        }
        if (FxPayService::verifySign()) {
            if ($data['status'] != 1){
                return 1;
            }
            //签名验证成功
            try {
                DB::transaction(function () use ($data) {
                    $bean_record = BeanRecord::where(['code' => $data['sdorderno']])->lockForUpdate()->first();
                    if (!$bean_record) {
                        throw new \Exception('记录不存在：' . $data['sdorderno'], -1);
                    }
                    if ($bean_record->status != 0) {
                        throw new \Exception('重复操作：' . $data['sdorderno'], -1);
                    }
                    //检查金额
                    if (bccomp($bean_record->price, $data['price'], 2) !== 0) {
                        throw new \Exception('订单金额与实际支付金额不一致：'. $data['sdorderno'], -1);
                    }
                    //入账
                    RechargeService::run($bean_record->user_id, $bean_record->bean, false);
                    //回调成功
                    $bean_record->trade_no = $data['platform_id'];
                    $bean_record->status = 1;
                    $bean_record->is_pay_api = 1;
                    $bean_record->save();
                });
                return 1;
            } catch (\Exception $e) {
                $data['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
                Log::error('富信卡支付异步通知出错', $data);
                return 0;
            }
        }
        return 0;
    }

    /**
     * UmiStrong 支付回调通知
     * @return string
     */
    public function umiStrongPay()
    {
        $data = request()->post();
        Log::info('Umi支付回调来了', $data);
        if (empty($data['customerid']) || empty($data['totalfee']) || empty($data['sdorderno']) || empty($data['sdpayno']) || empty($data['paytype']) || empty($data['sign'])) {
            Log::warning('Umi支付回调非法调用', $data);
            return 'fail';
        }
        //签名验证
        if (UmiStrongPayService::verifySign()) {
            DB::beginTransaction();
            try {
                $bean_record = BeanRecord::where(['code' => $data['sdorderno']])->lockForUpdate()->first();
                if (!$bean_record) {
                    throw new \Exception("记录不存在：" . $data['sdorderno'], -1);
                }
                if ($bean_record->status != 0) {
                    throw new \Exception("重复操作：" . $data['sdorderno'], -1);
                }
                if (bccomp($bean_record->price, $data['totalfee'], 2) !== 0) {
                    throw new \Exception("价格不相等：" . $data['sdorderno'], -1);
                }
                //入账
                RechargeService::run($bean_record->user_id, $bean_record->bean, false);
                //回调成功
                $bean_record->trade_no = $data['sdpayno'];
                $bean_record->status = 1;
                $bean_record->is_pay_api = 1;
                $bean_record->save();
                DB::commit();
                return 'success';
            } catch (\Exception $e) {
                DB::rollBack();
                if ($e->getCode() === -1) {
                    return 'success';
                } else {
                    Log::error('Umi支付异步通处理出错：' . $e->getMessage(), [
                        'Message' => $e->getMessage(),
                        'File' => $e->getFile(),
                        'Line' => $e->getLine()
                    ]);
                }
                return 'failure';
            }
        } else {
            return 'failure';
        }
    }

    /**
     * 华南vggStore支付回调通知
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|string
     * @throws \Throwable
     */
    public function vggStorePay()
    {
        $data = request()->post();
        Log::error('华南vggStore支付回调来了', $data);
        if (empty($data['amount'])
            || empty($data['notify_url'])
            || empty($data['orderno'])
            || empty($data['return_url'])
            || empty($data['title'])
            || empty($data['sign'])
            || empty($data['transaction'])
        ) {
            return response('fail', 401);
        }
        $sign_flag = VggStorePayService::verifySign();
        if ($sign_flag) {
            //签名验证成功
            try {
                DB::transaction(function () use ($data) {
                    $bean_record = BeanRecord::where(['code' => $data['orderno']])->lockForUpdate()->first();
                    if (!$bean_record) {
                        throw new \Exception('记录不存在：' . $data['orderno'], -1);
                    }
                    if ($bean_record->status != 0) {
                        throw new \Exception('重复操作：' . $data['orderno'], -1);
                    }
                    //检查金额
                    if (bccomp($bean_record->price, $data['amount'], 2) !== 0) {
                        throw new \Exception('价格不相等：' . $data['amount'], -1);
                    }
                    //入账
                    RechargeService::run($bean_record->user_id, $bean_record->bean, false);
                    //回调成功
                    $bean_record->trade_no = $data['transaction'];
                    $bean_record->status = 1;
                    $bean_record->is_pay_api = 1;
                    $bean_record->save();
                });
                return 'ok';
            } catch (\Exception $e) {
                $data['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
                Log::error('华南vggStore支付回调出错', $data);
                if ($e->getCode() == -1) {
                    $message = 'ok';
                    return $message;
                } else {
                    $message = 'fail';
                }
                return response($message,400);
            }
        }
        return response('fail',400);
    }

    /**
     * 华南hnsqpay支付回调通知
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|string
     * @throws \Throwable
     */
    public function hnsqPay()
    {
        $data = request()->post();
        Log::error('华南hnsqpay支付回调来了', $data);
        if (empty($data['amount'])
            || empty($data['notify_url'])
            || empty($data['orderno'])
            || empty($data['return_url'])
            || empty($data['title'])
            || empty($data['sign'])
            || empty($data['transaction'])
        ) {
            return response('fail', 401);
        }
        $sign_flag = HnsqPayService::verifySign();
        if ($sign_flag) {
            //签名验证成功
            try {
                DB::transaction(function () use ($data) {
                    $bean_record = BeanRecord::where(['code' => $data['orderno']])->lockForUpdate()->first();
                    if (!$bean_record) {
                        throw new \Exception('记录不存在：' . $data['orderno'], -1);
                    }
                    if ($bean_record->status != 0) {
                        throw new \Exception('重复操作：' . $data['orderno'], -1);
                    }
                    //检查金额
                    if (bccomp($bean_record->price, $data['amount'], 2) !== 0) {
                        throw new \Exception('价格不相等：' . $data['amount'], -1);
                    }
                    //入账
                    RechargeService::run($bean_record->user_id, $bean_record->bean, false);
                    //回调成功
                    $bean_record->trade_no = $data['transaction'];
                    $bean_record->status = 1;
                    $bean_record->is_pay_api = 1;
                    $bean_record->save();
                });
                return 'ok';
            } catch (\Exception $e) {
                $data['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
                Log::error('华南hnsqpay支付回调出错', $data);
                if ($e->getCode() == -1) {
                    $message = 'ok';
                    return $message;
                } else {
                    $message = 'fail';
                }
                return response($message,400);
            }
        }
        return response('fail',400);
    }
}
