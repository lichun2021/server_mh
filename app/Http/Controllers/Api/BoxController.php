<?php

namespace App\Http\Controllers\Api;

use App\Box;
use App\User;
use App\Skins;
use App\BoxCate;
use App\BoxLucky;
use App\BoxRecord;
use App\BoxContain;
use App\Jobs\TopHistory;
use App\BeanChangeRecord;
use App\Services\BoxService;
use Illuminate\Support\Facades\DB;
use App\Services\TopHistoryService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{
    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['boxList', 'detail', 'history', 'hotBox','topHistory']]);
    }

    /**
     * 宝箱列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function boxList()
    {
        $key = Box::$fields['cacheKey'][1];
        $data = Cache::get($key);
        if (empty($data)) {
            $boxs = BoxCate::with(['box' => function ($query) {
                return $query->select(['id', 'name', 'cover', 'weapon_cover', 'bean', 'cate_id'])
                    ->where('is_putaway', 1)->orderBy('sort');
            }])->select('id', 'name', 'src', 'sort')
                ->orderBy('sort')
                ->get()
                ->toArray();
            $data = $boxs;
            Cache::put($key, $data, 60);
        }

        return self::apiJson(200,'ok',$data);
    }

    /**
     * 热门宝箱
     * @return \Illuminate\Http\JsonResponse
     */
    public function hotBox()
    {
        $key = Box::$fields['cacheKey'][10];
        $data = Cache::get($key);
        if (empty($data)) {
            $boxs = Box::select(['id', 'name', 'intact_cover', 'bean', 'cate_id'])
                ->where('is_putaway', 1)
                ->orderByDesc('times')
                ->limit(12)
                ->get()
                ->toArray();
            $data = $boxs;
            Cache::put($key, $data,60);
        }
        return self::apiJson(200,'ok',$data);
    }

    /**
     * 宝箱详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer|min:1',
        ], [
            'id.required' => '宝箱编号不存在',
            'id.integer' => '宝箱编号错误',
            'id.min' => '宝箱编号错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }

        $boxId = request()->get('id');
        $key = Box::$fields['cacheKey'][8] . $boxId;
        $box = Cache::get($key);
        if ($box === null) {
            $box = Box::with(['contains' => function ($query) {
                return $query->with(['skins' => function($query){
                    return $query->select(['id', 'name', 'cover','image_url' , 'dura', 'bean']);
                }])->select(['box_contains.id', 'box_contains.box_id', 'box_contains.skin_id', 'box_contains.odds','box_contains.level'])
                    ->join('skins','skins.id','=','box_contains.skin_id')
                    ->orderByDesc('skins.bean');
            }])->select('id', 'name', 'cover', 'weapon_cover', 'intact_cover', 'bean')
                ->where('id', $boxId)
                ->first();
            if ($box === null){
                return self::apiJson(500,'宝箱编号错误');
            }
            $box->append(['odds_list']);
            $box->contains->append(['odds_percent','level_image_url']);
            $box = $box->toArray();
            Cache::put($key, $box,60);
        }

        if (!$box) {
            return self::apiJson(500,'信息不存在');
        }
        return self::apiJson(200,'ok',$box);
    }


    /**
     * 开箱
     *
     * @param int $id 宝箱ID
     * @param int $num 数量
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function open()
    {
        //var_dump(Redis::lrange('open_box_list_id_3',0,-1));die;
        $validator = Validator::make(request()->post(), [
            'id' => 'required|integer|min:1',
            'num' => 'required|integer|min:1|max:5',
        ], [
            'id.required' => '缺少宝箱Id',
            'num.required' => '缺少开箱数量',
            'id.integer' => '宝箱Id错误',
            'id.min' => '宝箱Id错误',
            'num.min' => '开箱数量最小值1',
            'num.max' => '开箱数量最大值5',

        ]);

        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }

        $box_id = request()->post('id');
        $num = request()->post('num');
        //原子锁
        if (auth('api')->user()->anchor == 1) {
            $lockKey = Box::$fields['cacheKey'][3] . $box_id;
        } else {
            $lockKey = Box::$fields['cacheKey'][2] . $box_id;
        }
        //拿到Cache原子锁 最多锁十秒
        $cacheLock = Cache::lock($lockKey, 10);

        try {
            //返回结果
            $list = [];
            //数据库事务处理
            DB::transaction(function () use (&$list, $cacheLock, $box_id, $num) {
                $box = Box::where(['id' => $box_id, 'is_putaway' => 1])->first();
                if (!$box) {
                    throw new \Exception("宝箱不存在", -1);
                }
                $total_bean = bcmul($box->bean, $num, 2);
                $user = User::where('id', auth('api')->id())->lockForUpdate()->first();
                if ($user->bean < $total_bean) {
                    throw new \Exception(getConfig('bean_name').'不足！', -1);
                } elseif ($user->is_recharge === 0){
                    throw new \Exception('进行任意额度充值即可开箱提取，为了维护广大用户的合法权益，防止机器人恶意注册,谢谢理解!', -1);
                }
                //减少金豆
                $user->decrement('bean', $total_bean);
                //写收支明细
                BeanChangeRecord::add(0,1,-$total_bean);
                //增加亏损
                $user->increment('loss', $total_bean);
                //增加开启次数
                $box->increment('times', $num);
                //增加积分
                $user->increment('integral', $total_bean);
                //根据用户对应Cache Key
                if ($user->anchor == 1) {
                    //主播爆率KEY
                    $box_lucky_field = 'luck_interval_anchor';
                    $lucky_record_field = 'luck_anchor_value';
                } else {
                    //普通用户
                    $box_lucky_field = 'luck_interval';
                    $lucky_record_field = 'luck_value';
                }
                //10秒内拿不到锁抛出异常
                $cacheLock->block(10);

                //随机抽取奖励
                $ids = [];
                for ($i = 0; $i < $num; $i++) {
                    //查询箱子幸运值记录
                    $lucky_record = BoxLucky::where('box_id', $box_id)->first();
                    //不存在时创建
                    if ($lucky_record === null) {
                        $lucky_record = BoxLucky::createBoxRecord($box);
                    }

                    if ($lucky_record->$lucky_record_field <= 0) {
                        //触发幸运开箱
                        $award_id = BoxService::getBoxLuckSkins($box_id);
                        //重新生成主播幸运值保存
                        $lucky_record->$lucky_record_field = $box->$box_lucky_field;
                        $lucky_record->save();
                    } else {
                        //战损回血KEY
                        $lossKey = 'user:loss_trigger_times'.$user->id;
                        if ($user->is_loss === 1){
                            //战损回血
                            $res = BoxContain::select(['box_contains.id','box_contains.skin_id','skins.bean'])
                                ->leftJoin('skins','skins.id','box_contains.skin_id')
                                ->where(['box_contains.box_id' => $box_id])
                                ->where('real_odds','>=',1)
                                ->where('skins.bean','<=',bcmul(User::find($user->id)->loss,getConfig('loss_return_ratio'),2))
                                ->where('skins.bean','>',$box->bean)
                                ->get()
                                ->toArray();
                            if ($res){
                                //符合条件回血
                                $resRandKey = array_rand($res);
                                $award_id = $res[$resRandKey]['skin_id'];
                                //统计回血次数
                                $lossTimes = Cache::increment($lossKey);
                            } else {
                                //减幸运值
                                $lucky_record->decrement($lucky_record_field);
                                //普通开箱
                                $award_id = BoxService::getBoxSkins($box_id);
                            }
                        } else {
                            //普通开箱
                            $lucky_record->decrement($lucky_record_field);
                            //减幸运值
                            $award_id = BoxService::getBoxSkins($box_id);
                        }
                        //亏损小于0 或累计触发5次时自动关闭回血
                        if (User::find($user->id)->loss <= 0 || isset($lossTimes) && $lossTimes >= 10){
                            Cache::delete($lossKey);
                            User::where(['id' => $user->id])->update(['is_loss' => 0]);
                        }

                    }

                    //结束----------------
                    //新修改入库
                    $box_record = new BoxRecord();
                    $skins = Skins::find($award_id);
                    //减去亏损
                    $user->decrement('loss',$skins->bean);
                    $contain = BoxContain::select('level')->where(['box_id' => $box_id,'skin_id' => $skins->id])->first();
                    $box_record->get_user_id = $user->id;
                    $box_record->user_id = $user->id;
                    $box_record->box_id = $box->id;
                    $box_record->box_name = $box->name;
                    $box_record->box_bean = $box->bean;
                    $box_record->skin_id = $skins->id;
                    $box_record->name = $skins->name;
                    $box_record->cover = $skins->getRawOriginal('cover');
                    $box_record->dura = $skins->dura;
                    $box_record->lv = $contain->level;
                    $box_record->bean = $skins->bean;
                    $box_record->code = getUniqueOrderNumber();
                    $box_record->type = 1;
                    $box_record->is_purse = $skins->is_purse;
                    $box_record->save();
                    $box_record->append(['dura_alias', 'lv_alias', 'lv_bg_image']);
                    $list[] = $box_record->makeHidden(['get_user_id', 'user_id', 'box_id', 'box_name', 'box_bean', 'skin_id', 'uuid', 'type', 'created_at', 'updated_at']);
                    $ids[] = $box_record->id;
                }
                //加入列队
                //TopHistory::dispatch($ids)->delay(now()->addSeconds(5));
                TopHistory::dispatch($ids);
                //释放锁
                $cacheLock->release();
            });
        } catch (\Exception $e) {
            $cacheLock->release();
            $message = '开箱失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }
            return self::apiJson(500,$message);
        }
        return self::apiJson(200,'ok',$list);
    }

    /**
     * 开箱记录
     * @return \Illuminate\Http\JsonResponse
     */
    public function history()
    {
        $validator = Validator::make(request()->post(), [
            'id' => 'integer|min:1',
            'type_id' => 'integer|min:1',
            'uid' => 'integer|min:1',
        ],[
            'id.integer' => '宝箱编号错误',
            'id.min' => '宝箱编号错误',
            'type_id.integer' => '类型编号错误',
            'type_id.min' => '类型编号错误',
            'uid.integer' => '用户Id错误',
            'uid.min' => '用户Id错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }

        $boxId = request()->get('id');
        $typeId = request()->get('type_id');
        $uid = request()->get('uid');
        //防刷缓存
        $key = BoxRecord::$fields['cacheKey']. $boxId.'_'.$typeId;
        $box_records = Cache::get($key);
        if ($box_records === null){
            $query = BoxRecord::with(['get_user:id,name,avatar','box' => function($query){
                return $query->select(['id', 'name', 'intact_cover']);
            },'skins' => function($query){
                return $query->select(['id','dura','rarity']);
            }])->select(['id', 'get_user_id', 'box_id','skin_id', 'box_name', 'box_bean', 'name', 'cover', 'dura', 'lv', 'bean', 'type']);

            if ($boxId) {
                $query->where('box_id', $boxId);
            }
            if ($uid){
                $query->where('get_user_id', $uid);
            }

            if ($typeId){
                $query->where('type',$typeId);
            } else {
                $query->whereIn('type', [1, 2, 3, 4, 5, 6]);
            }

            $box_records = $query->orderBy('id', 'DESC')->limit(16)->get();
            $box_records->append(['profit_ratio', 'lv_alias', 'lv_bg_image', 'dura_alias', 'type_alias']);
            $box_records = $box_records->toArray();
            Cache::put($key,$box_records,5);
        }
        return self::apiJson(200,'ok',$box_records);
    }

    /**
     * 顶部滚动开箱记录
     * @return \Illuminate\Http\JsonResponse
     */
    public function topHistory()
    {
        return self::apiJson(200,'ok',TopHistoryService::run());
    }

    /**
     * 提取奖品
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function extract()
    {
        $validator = Validator::make(request()->post(), [
            'data' => ['required', 'array', 'min:1'],
        ],[
            'data.required' => '请选择饰品',
            'data.array' => '提交数据错误',
            'data.min' => '提交数据错误',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }

        //禁止提取提示
        if (auth('api')->user()->ban_pick_up == 1) {
            if (auth('api')->user()->anchor == 1) {
                return self::apiJson(500,'亲爱的主播大人，您的账号不允许提货哦！');
            } else {
                return self::apiJson(500,'您的账户近期无充值记录，充值任意金额即可取货！');
            }
        }
        //steam
        if (empty(auth('api')->user()->steam_url)) {
            return self::apiJson(500,'您未填写steam收货链接，请先填写steam交易链接！');
        }

        try {
            DB::transaction(function () {
                $data =request()->post('data');
                foreach ($data as $id) {
                    if (is_numeric($id)){
                        $box_record = BoxRecord::where('id', $id)->lockForUpdate()->first();
                        if (!$box_record || $box_record->user_id != auth('api')->id()) {
                            throw new \Exception('饰品信息不存在！', -1);
                        } elseif ($box_record->status != 0) {
                            throw new \Exception('饰品已操作！', -1);
                        } elseif ($box_record->is_purse === 1) {
                            throw new \Exception('钱袋或福袋类型物品无法提取！', -1);
                        }
                        $box_record->status = 4;
                        if (!$box_record->save()) {
                            throw new \Exception('系统错误', -1);
                        }
                    } else {
                        throw new \Exception('非法请求！', -1);
                    }
                }
            });
        } catch (\Exception $e) {
            $message = '系统错误，提货申请失败！';
            //写错误日志
            \Log::debug('==========饰品回收失败===========',['message' => $e->getMessage(),'code' => $e->getCode(),'file' => $e->getFile(),'line' => $e->getLine(),'user_id' => auth('api')->id()]);
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }
            return self::apiJson(500,$message);
        }
        return self::apiJson(200,'提货申请提交成功！');
    }

    /**
     * 兑换
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function cash()
    {
        $validator = Validator::make(request()->post(), [
            'data' => ['required', 'array','min:0']
        ],[
            'data.required' => '请选择饰品',
            'data.array' => '提交错误的数据',
            'data.min' => '提交错误的数据',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500,$validator->errors()->first());
        }
        //未充值禁止回收
        /*if (auth('api')->user()->anchor != 1 && auth('api')->user()->total_recharge <= 0) {
            return self::apiJson(500,'尊敬的用户，您的回收功能未开放，充值任意金额自动开放！');
        }*/

        try {
            DB::transaction(function () {
                $data = request()->post('data');
                $total_bean = 0;
                foreach ($data as $id) {
                    $box_record = BoxRecord::where('id', $id)->lockForUpdate()->first();
                    if (!$box_record || $box_record->user_id != auth('api')->id()) {
                        throw new \Exception("饰品信息不存在！", -1);
                    }
                    if ($box_record->status != 0) {
                        throw new \Exception("饰品已操作", -1);
                    }
                    $total_bean += $box_record->bean;
                    $box_record->status = 2;
                    $box_record->save();
                }
                //入账
                User::where('id', auth('api')->id())->increment('bean', $total_bean);
                //写收支明细
                BeanChangeRecord::add(1,4,$total_bean);
            });
        } catch (\Exception $e) {
            $message = '饰品回收失败！';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            } else {
                \Log::debug('==========饰品回收失败===========',['message' => $e->getMessage(),'code' => $e->getCode(),'file' => $e->getFile(),'line' => $e->getLine(),'user_id' => auth('api')->id()]);
            }
            return self::apiJson(500,$message);
        }

        return self::apiJson(200,'饰品回收成功！');
    }
}
