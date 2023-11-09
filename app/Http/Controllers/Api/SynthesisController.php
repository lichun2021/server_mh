<?php

namespace App\Http\Controllers\Api;

use App\Skins;
use App\User;
use App\BoxRecord;
use App\SyntheRecord;
use App\Jobs\TopHistory;
use App\Services\SynthesisService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * Class SynthesisController
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/2/25
 * Time：22:13
 */
class SynthesisController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['list']]);
    }

    /**
     * 合成
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function run()
    {
        $validator = Validator::make(request()->post(), [
            'in_ids' => ['required', 'array', 'min:3', 'max:10']
        ], [
            'in_ids.required' => '请选择饰品',
            'in_ids.array' => '参数错误',
            'in_ids.min' => '最少选择3件饰品',
            'in_ids.max' => '最多选择10件饰品',
        ]);
        if ($validator->fails()) {
            return self::apiJson(500, $validator->errors()->first());
        }

        $list = [];

        try {
            DB::transaction(function () use (&$list) {

                $total_bean = 0;
                $in_ids = request()->post('in_ids');
                foreach ($in_ids as $in_id) {
                    $box_record = BoxRecord::where('id', $in_id)->lockForUpdate()->first();
                    if (!$box_record || $box_record->user_id != auth('api')->id() || $box_record->status != 0) {
                        throw new \Exception('仓库饰品不存在！', -1);
                    } elseif ($box_record->is_purse === 1) {
                        throw new \Exception('钱袋或福袋类型物品汰换无法使用！', -1);
                    }
                    //累加装备总价值
                    $total_bean += $box_record->bean;
                    //改变装备状态
                    $box_record->status = 2;
                    $box_record->save();
                }

                $minBean = getConfig('synthesis_min_bean');
                if ($total_bean < $minBean) {
                    throw new \Exception('汰换饰品合计总价不得低于' . $minBean . getConfig('bean_name'), -1);
                }

                $skinId = SynthesisService::getSkinId($total_bean);
                if ($skinId === false) {
                    throw new \Exception('没有可汰换的饰品！', -1);
                }
                $skin = Skins::find($skinId);

                //写记录
                $syntheRecord = new SyntheRecord();
                $syntheRecord->user_id = auth('api')->id();
                $syntheRecord->use_bean = $total_bean;
                $syntheRecord->award_id = $skin->id;
                $syntheRecord->award_name = $skin->name;
                $syntheRecord->award_dura = $skin->dura;
                $syntheRecord->bean = $skin->bean;

                if ($skin->bean > $total_bean) {
                    //减去战损
                    User::where('id', auth('api')->id())->decrement('loss', $skin->bean - $total_bean);
                    $syntheRecord->status = 1;
                    $syntheRecord->save();
                } elseif ($skin->bean < $total_bean) {
                    //增加亏损
                    User::where('id', auth('api')->id())->increment('loss', $total_bean - $skin->bean);
                    $syntheRecord->status = 0;
                    $syntheRecord->save();
                } else {
                    $syntheRecord->status = 2;
                    $syntheRecord->save();
                }


                $box_record = new BoxRecord();
                $box_record->get_user_id = auth('api')->id();
                $box_record->user_id = auth('api')->id();
                $box_record->box_id = 0;
                $box_record->box_name = '汰换合约';
                $box_record->box_bean = $total_bean;
                $box_record->skin_id = $skin->id;
                $box_record->name = $skin->name;
                $box_record->cover = $skin->getRawOriginal('cover');
                $box_record->dura = $skin->dura;
                $box_record->bean = $skin->bean;
                $box_record->code = getUniqueOrderNumber();
                $box_record->type = 7;
                $box_record->is_purse = $skin->is_purse;
                $box_record->save();

                $list = $box_record;

                TopHistory::dispatch([$box_record->id]);
            });
        } catch (\Exception $e) {
            $message = '合成失败';
            if ($e->getCode() == -1) {
                $message = $e->getMessage();
            }

            return response()->json([
                'code' => 500,
                'message' => $message
            ]);
        }
        if (empty($list)) {
            return response()->json([
                'code' => 200,
                'message' => '很遗憾，饰品合成失败！'
            ]);
        } else {
            return response()->json([
                'code' => 200,
                'message' => 'OK',
                'data' => $list

            ]);
        }
    }

    /**
     * 可合成列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $query = Skins::query()->where(['is_synthesis' => 1])
            ->select(['id', 'name', 'cover', 'dura', 'bean'])
            ->orderBy('id')
            ->Paginate(20);
        return self::apiJson(200, 'ok', $query);
    }
}
