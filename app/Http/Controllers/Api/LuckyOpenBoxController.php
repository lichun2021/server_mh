<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/26
 * Time: 22:43
 */

namespace App\Http\Controllers\Api;

use App\BeanChangeRecord;
use App\Jobs\TopHistory;
use App\Skins;
use App\User;
use App\SkinsType;
use App\BoxRecord;
use App\LuckyBoxRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
class LuckyOpenBoxController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['type','list','search','detail','history']]);
    }

    /**
     * 幸运开箱装备类型
     * @return \Illuminate\Http\JsonResponse
     */
    public function type()
    {
        $key = SkinsType::$fields['cacheKey'][1];
        $data = Cache::get($key);
        if ($data === null){
            $data = SkinsType::orderBy('sort')
                ->get()
                ->toArray();
            Cache::put($key,$data);
        }

        return self::apiJson(200,'ok',$data);
    }


    /**
     * 幸运开箱装备类型列表
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    /**
     * 幸运开箱装备类型列表
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function list()
    {
        $validator = Validator::make(request()->post(), [
            'type_id' => 'required|integer|min:0',
            'name' => 'string',
            'start' => 'integer|min:0',
            'end' => 'integer|min:0',
            'sort' => 'integer|in:0,1',
            'page' => 'integer|min:1',
        ], [
            'type_id.required' => '缺少类型Id',
            'type_id.integer' => '类型ID错误',
            'type_id.min' => '类型ID错误',
            'name.string' => '搜索关键词输入有误',
            'start.integer' => '间隔区间起始值只能是数字',
            'start.min' => '间隔区间起始值最小值不能小于0',
            'end.integer' => '间隔区间结束值只能是数字',
            'end.min' => '间隔区间结束值最小值不能小于0',
            'sort.integer' => '参数类型错误',
            'sort.in' => '参数范围错误',
            'page.integer' => '页码错误',
            'page.min' => '页码错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }
        $type_id = request()->get('type_id');
        $name = request()->get('name');
        $start = request()->get('start');
        $end = request()->get('end');
        $sort = request()->get('sort');
        $sort = empty($sort) ? 'orderByDesc' : 'orderBy';

        //装备类型
        if ($type_id == 0) {
            $data = Skins::select('id', 'name', 'cover', 'dura', 'bean');
        } else {
            $data = Skins::select('id', 'name', 'cover', 'dura', 'bean')
                ->where('type', $type_id);
        }
        //搜索
        if (!empty($name)) {
            $data = $data->where(function ($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%')
                    ->orWhere('hash_name', 'like', '%' . $name . '%');
            });
        }
        if (!empty($start)){
            $data = $data->where('bean','>=',$start);
        }
        if (!empty($end)){
            $data = $data->where('bean','<=',$end);
        }
        $data = $data->where('is_lucky_box', 1)
            ->$sort('bean')
            ->paginate(32)
            ->toArray();


        return self::apiJson(200, 'ok', $data);
    }


    /**
     * 搜索装备
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $validator = Validator::make(request()->post(), [
            'name' => 'required|string',
            'page' => 'integer|min:1',
        ], [
            'name.required' => '请输入饰品关键词',
            'name.string' => '关键词输入有误',
            'page.integer' => '页码错误',
            'page.min' => '页码错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500, $errors);
        }

        $name = request()->get('name');

        $data = Skins::select('id', 'name', 'cover', 'dura', 'bean')
            ->where('is_lucky_box', 1)
            ->where('name', 'like', '%' . $name . '%')
            ->orWhere('hash_name','like','%'.$name.'%')
            ->orderByDesc('bean')
            ->paginate(56)
            ->toArray();

        return self::apiJson(200, 'ok', $data);
    }


    /**
     * 幸运开箱
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function open()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer',
            'percent' => 'required|numeric|between:0.05,0.75'
        ], [
            'id.required' => '饰品Id不能为空',
            'id.integer' => '饰品编号错误',
            'percent.required' => '请输入成功率',
            'percent.numeric' => '参数错误',
            'percent.between' => '成功率不在规定值内',
        ]);

        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }

        $list = [];

        $award_id = request()->post('id');
        $percent = request()->post('percent');
        //缓存
        if (auth('api')->user()->anchor == 1){
            $lock_key = Skins::$fields['cacheKey'][3].$award_id;
            $cache_key = Skins::$fields['cacheKey'][5].$award_id;
            $field = 'luck_interval_anchor';
            $lock = Cache::lock($lock_key, 10);
        } else {
            $lock_key = Skins::$fields['cacheKey'][2].$award_id;
            $cache_key = Skins::$fields['cacheKey'][4].$award_id;
            $field = 'luck_interval';
            $lock = Cache::lock($lock_key, 10);
        }
        try {
            /*if (!in_array(auth('api')->id(),[104648,104646,104637,104649])) {
                throw new \Exception("系统异常正在维护", -1);
            }*/
            //数据库事务处理
            DB::transaction(function () use (&$list, $award_id,$percent,$cache_key,$field,$lock) {
                $user = User::where('id', auth('api')->id())->lockForUpdate()->first();

                $skins = Skins::find($award_id);
                $getSkins = $skins;

                if (!$skins || $skins->is_lucky_box != 1 || empty($skins->luck_interval) || empty($skins->luck_interval_anchor)) {
                    throw new \Exception('幸运饰品编号不存在！', -1);
                }

                $total_bean = bcmul($skins->bean, $percent, 2);

                if ($user->bean < $total_bean) {
                    throw new \Exception(getConfig('bean_name').'不足！', -1);
                } elseif ($user->is_recharge === 0){
                    throw new \Exception('进行任意额度充值即可开箱提取，为了维护广大用户的合法权益，防止机器人恶意注册,谢谢理解!', -1);
                }
                //减少金豆
                $user->decrement('bean', $total_bean);
                //增加亏损
                $user->increment('loss', $total_bean);
                //增加积分
                $user->increment('integral', $total_bean);
                //写收支记录
                BeanChangeRecord::add(0,3,-$total_bean);
                //上锁
                $lock->block(10);
                //生成幸运区间值
                $interval = $skins->generateInterval($skins->$field);
                //因Redis不支持小数点 乘以100
                $interval = $interval * 100;

                $total_cost = Cache::get($cache_key);
                //没有值生成区间值
                if ($total_cost === null){
                    Cache::put($cache_key,$interval);
                }
                //支出 因Redis不支持小数点 乘以100
                $expend =  $total_bean * 100;
                //减去消费 返回剩余数
                $surplus = Cache::decrement($cache_key, $expend);

                if ($surplus <= 0){
                    //小于等于0爆出 物品 重设数值
                    $box_record = new BoxRecord();

                    $box_record->get_user_id = auth('api')->id();
                    $box_record->user_id = auth('api')->id();
                    $box_record->box_id = 0;
                    $box_record->box_name = '幸运开箱';
                    $box_record->box_bean = $total_bean;
                    $box_record->skin_id = $skins->id;
                    $box_record->name = $skins->name;
                    $box_record->cover = $skins->getRawOriginal('cover');
                    $box_record->dura = $skins->dura;
                    $box_record->bean = $skins->bean;
                    $box_record->code = getUniqueOrderNumber();
                    $box_record->type = 4;
                    $box_record->is_purse = $skins->is_purse;
                    $box_record->save();
                    $list = $box_record;
                    //重置区间值
                    Cache::put($cache_key,$interval);
                } else {
                    if ($total_bean > 1) {
                        $bean = 0.3;
                    } else {
                        $bean = 0.1;
                    }

                    $awards = Skins::whereIn('type',[1,2,3,4,5,7])->where('bean','<=',$bean)->pluck('id')->toArray();
                    //数据空时替补
                    if (empty($awards)){
                        $awards = Skins::where('bean','<=',$bean)->pluck('id')->toArray();
                    }
                    if (empty($awards)){
                        throw new \Exception('系统数据异常！', -1);
                    }
                    $andomInt = array_rand($awards, 1);
                    $skins_id = $awards[$andomInt];

                    $skins = Skins::find($skins_id);
                    $box_record = new BoxRecord();
                    $box_record->get_user_id = auth('api')->id();
                    $box_record->user_id = auth('api')->id();
                    $box_record->box_id = 0;
                    $box_record->box_name = '幸运开箱';
                    $box_record->box_bean = $total_bean;
                    $box_record->skin_id = $skins->id;
                    $box_record->name = $skins->name;
                    $box_record->cover = $skins->getRawOriginal('cover');
                    $box_record->dura = $skins->dura;
                    $box_record->bean = $skins->bean;
                    $box_record->code = getUniqueOrderNumber();
                    $box_record->type = 4;
                    $box_record->is_purse = $skins->is_purse;
                    $box_record->save();
                    //var_dump($box_record);die;
                    $list = $box_record;
                }
                $list->append(['dura_alias']);
                //写幸运开箱记录
                $luckyBoxRecord = new LuckyBoxRecord();
                $luckyBoxRecord->user_id = auth('api')->id();
                $luckyBoxRecord->use_bean = $total_bean;
                $luckyBoxRecord->percent = $percent * 100;
                $luckyBoxRecord->award_id = $getSkins->id;
                $luckyBoxRecord->award_name = $getSkins->name;
                $luckyBoxRecord->award_dura = $getSkins->dura;
                $luckyBoxRecord->bean = $getSkins->bean;
                $luckyBoxRecord->get_award_id = $list['skin_id'];
                $luckyBoxRecord->get_award_name = $list['name'];
                $luckyBoxRecord->get_award_dura = $list['dura'];
                $luckyBoxRecord->get_bean = $list['bean'];
                $luckyBoxRecord->save();
                //减去亏损
                $user->decrement('loss',$list['bean']);
                //释放锁
                $lock->release();
                //Top
                TopHistory::dispatch([$box_record->id]);
            });
        } catch (\Exception $e) {
            $message = '开箱失败';
            //释放锁
            if($e->getMessage() == '系统数据异常！'){
                \Cache::delete($cache_key);
            }
            $lock->release();
            //$message = $e->getMessage();
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }
            return self::apiJson(500,$message);
        }

        return self::apiJson(200,'ok',$list);
    }


    /**
     * 根据ID获取装备信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer|min:1|exists:skins,id',
        ], [
            'id.required' => '缺少饰品Id',
            'id.integer' => '饰品Id错误',
            'id.min' => '饰品Id错误',
            'id.exists' => '饰品Id错误',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return self::apiJson(500,$errors);
        }
        $id = request()->get('id');
        $key = getCacheKey('LuckyOpenBoxAward').$id;

        $data = Cache::get($key);

        if (empty($data)){
            $data = Skins::query()
                ->select('id','name','cover','dura','bean')
                ->where('id',$id)
                ->where('is_lucky_box',1)
                ->first()
                ->toArray();
            if (!empty($data)){
                Cache::put($key,$data,60);
            }
        }
        return self::apiJson(200,'ok',$data);
    }

    /**
     * 最近掉落
     * @return \Illuminate\Http\JsonResponse
     */
    public function history()
    {
        $validator = Validator::make(request()->query(), [
            'id' => 'required|integer|exists:skins,id'
        ], [
            'id.required' => '缺少参数',
            'id.integer' => '参数错误',
            'id.exists' => '请求Id无效'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            return response()->json([
                'code' => 500,
                'message' => $errors
            ]);
        }
        $awardId = request()->get('id');
        return response()->json([
            'code' => 200,
            'message' => 'ok',
            'data' => LuckyBoxRecord::with(['user:id,name,avatar','get_award:id,name,dura,cover'])
                ->where('award_id', $awardId)
                ->where('percent','>',0)
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->append(['award_dura_alias','get_award_dura_alias'])
                ->toArray()
        ]);
    }
}
