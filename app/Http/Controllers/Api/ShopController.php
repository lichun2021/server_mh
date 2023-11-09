<?php


namespace App\Http\Controllers\Api;

use App\Jobs\TopHistory;
use App\Skins;
use App\User;
use App\BoxRecord;
use App\BeanChangeRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * Class ShopController
 * @package App\Http\Controllers\Api
 * @author <860646000@qq.com>
 */
class ShopController extends Controller
{

    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['index']]);
    }

    /**
     * 商品列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $validator = Validator::make(request()->input(), [
            'name' => ['string'],
            'sort' => ['integer', 'in:0,1'],
            'min_bean' => ['numeric', 'min:0'],
            'max_bean' => ['numeric', 'min:0.1', 'max:10000000'],
            'type' => ['integer', 'min:1', 'max:255'],
            'dura' => ['integer', 'min:0', 'max:255'],
            'rarity' => ['integer', 'min:0', 'max:255'],
        ], [
            'name' => '搜索装备关键词错误',
            'sort.integer' => '排序参数错误',
            'sort.in' => '排序参数错误',
            'min_bean.numeric' => '价格区间开始值输入有误',
            'min_bean.min' => '价格区间开始值输入有误',
            'max_bean.numeric' => '价格区间结束值输入有误',
            'max_bean.min' => '价格区间结束值必须大于0.1',
            'max_bean.max' => '价格区间结束值输入有误',
            'type.integer' => '装备类型错误',
            'type.min' => '装备类型错误',
            'type.max' => '装备类型错误',
            'dura.integer' => '外观错误',
            'dura.min' => '外观错误',
            'dura.max' => '外观错误',
            'rarity.integer' => '稀有程度错误',
            'rarity.min' => '稀有程度错误',
            'rarity.max' => '稀有程度错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        $mall_bean_rate = 1 + bcdiv(getConfig('mall_bean_rate'), 100, 2);
        $mall_integral_rate = getConfig('mall_integral_rate');
        $query = Skins::select(['id', 'name', 'hash_name', 'cover', 'dura', 'rarity', DB::raw('TRUNCATE(bean * ' . $mall_bean_rate . ',2) AS mall_bean'), DB::raw('TRUNCATE(bean * ' . $mall_integral_rate . ',2) AS mall_integral')]);
        //上架筛选
        $query = $query->where('is_shop', 1);
        //装备名称
        if (!empty(request()->get('name'))) {
            $skinsName = trim(request()->get('name'));
            $query->where('name', 'like', '%' . $skinsName . '%')
                ->orWhere('hash_name', 'like', '%' . $skinsName . '%');
        }
        //排序
        if (!empty(request()->get('sort')) && request()->get('sort') == '0') {
            $query = $query->orderBy('bean');
        } elseif (request()->get('sort') == 1) {
            $query = $query->orderByDesc('bean');
        }
        //区间开始价格
        if (!empty(request()->get('min_bean')) && request()->get('min_bean') >= 0) {
            $query = $query->where('bean', '>=', bcdiv(request()->get('min_bean'), $mall_integral_rate, 2));
        }
        //区间结束价格
        if (!empty(request()->get('max_bean')) != null && request()->get('max_bean') >= 1) {
            $query = $query->where('bean', '<=', bcdiv(request()->get('max_bean'), $mall_integral_rate, 2));
        }
        //装备类型
        if (!empty(request()->get('type'))) {
            $query = $query->where('type', request()->get('type'));
        }
        //装备外观
        if (request()->get('dura') != '' && request()->get('dura') != null) {
            $query = $query->where('dura', request()->get('dura'));
        }
        //稀有程度
        if (request()->get('rarity') != '' && request()->get('rarity') != null) {
            $query = $query->where('rarity', request()->get('rarity'));
        }
        $data = $query->paginate(50);
        $data->append(['rarity_alias']);

        return self::apiJson(200, 'ok', $data);
    }

    /**
     * 购买
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function buy()
    {
        return self::apiJson(500, '饰品商城维护中，暂无法购买！');
        $validator = Validator::make(request()->post(), [
            'id' => ['required', 'integer', 'min:1'],
            'num' => ['required', 'integer', 'min:1', 'max:100']
        ], [
            'id.required' => '请选择饰品',
            'id.integer' => '购买饰品Id错误',
            'id.min' => '购买饰品Id错误',
            'num.required' => '请输入购买数量',
            'num.integer' => '购买饰品数量错误',
            'num.min' => '购买饰品数量错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        try {
            DB::transaction(function () {
                $num = request()->post('num');
                $skins = Skins::where(['id' => request()->post('id'), 'is_shop' => 1])->first();
                if (!$skins) {
                    throw new \Exception('购买饰品不存在!', -1);
                }
                $mall_bean_rate = 1 + bcdiv(getConfig('mall_bean_rate'), 100, 2);
                $skin_one_bean = bcmul($skins->bean, $mall_bean_rate, 2);
                $total_bean = bcmul($skin_one_bean, $num, 2);
                $user = User::where('id', auth('api')->id())->lockForUpdate()->first();
                if ($user->bean < $total_bean) {
                    throw new \Exception(getConfig('bean_name') . '不足', -1);
                }
                //减少金豆
                $user->decrement('bean', $total_bean);
                //写记录
                BeanChangeRecord::add(0, 14, -$total_bean);

                $ids = [];
                for ($i = 0; $i < $num; $i++) {

                    $box_record = new BoxRecord();
                    $box_record->get_user_id = auth('api')->id();
                    $box_record->user_id = auth('api')->id();
                    $box_record->box_id = 0;
                    $box_record->box_name = '饰品商城';
                    $box_record->box_bean = $skin_one_bean;
                    $box_record->skin_id = $skins->id;
                    $box_record->name = $skins->name;
                    $box_record->cover = $skins->getRawOriginal('cover');
                    $box_record->dura = $skins->dura;
                    $box_record->bean = $skins->bean;
                    $box_record->code = getUniqueOrderNumber();
                    $box_record->type = 5;
                    $box_record->is_purse = $skins->is_purse;
                    $box_record->save();
                    $ids[] = $box_record->id;
                }
                TopHistory::dispatch($ids);
            });
        } catch (\Exception $e) {
            $message = '购买失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }
            //记录错误
            Log::error('饰品购买出错', [
                'Message' => $e->getMessage(),
                'File' => $e->getFile(),
                'Line' => $e->getLine(),
            ]);
            return self::apiJson(500, $message);
        }
        return self::apiJson(200, '购买成功');
    }

    /**
     * 积分兑换饰品
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function exchange()
    {
        $validator = Validator::make(request()->post(), [
            'id' => ['required', 'integer', 'min:1'],
            'num' => ['required', 'integer', 'min:1', 'max:100']
        ], [
            'id.required' => '请选择饰品',
            'id.integer' => '兑换饰品Id错误',
            'id.min' => '兑换饰品Id错误',
            'num.required' => '请输入兑换数量',
            'num.integer' => '兑换饰品数量错误',
            'num.min' => '兑换饰品数量错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500,$errors);
        }

        try{
            DB::transaction(function () {
                $num = request()->post('num');
                $skins = Skins::where(['id' => request()->post('id'),'is_shop' => 1])->first();
                if (!$skins){
                    throw new \Exception('兑换饰品不存在!', -1);
                }
                $skin_one_integral = bcmul($skins->bean,getConfig('mall_integral_rate'),2);
                $total_integral = bcmul($skin_one_integral, $num, 2);
                $user = User::where('id', auth('api')->id())->lockForUpdate()->first();
                if ($user->integral < $total_integral) {
                    throw new \Exception("消费积分不足", -1);
                }
                //减少积分
                $user->decrement('integral', $total_integral);

                $ids = [];
                for ($i = 0;$i < $num;$i++){

                    $box_record = new BoxRecord();

                    $box_record->get_user_id = auth('api')->id();
                    $box_record->user_id = auth('api')->id();
                    $box_record->box_id = 0;
                    $box_record->box_name = '积分商城';
                    $box_record->box_bean = 0;
                    $box_record->skin_id = $skins->id;
                    $box_record->name = $skins->name;
                    $box_record->cover = $skins->getRawOriginal('cover');
                    $box_record->dura = $skins->dura;
                    $box_record->bean = $skins->bean;
                    $box_record->code = getUniqueOrderNumber();
                    $box_record->type = 5;
                    $box_record->is_purse = $skins->is_purse;
                    $box_record->save();
                    $ids[] = $box_record->id;
                }
                TopHistory::dispatch($ids);
            });
        }catch (\Exception $e){
            $message = '兑换失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }
            //记录错误
            Log::error('饰品兑换出错', [
                'Message' => $e->getMessage(),
                'File' => $e->getFile(),
                'Line' => $e->getLine(),
            ]);
            return self::apiJson(500,$message);
        }
        return self::apiJson(200,'兑换成功');
    }
}
