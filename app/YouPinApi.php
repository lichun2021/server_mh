<?php

namespace App;

use App\Services\YouPinService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class YouPinApi
 * NameSpace App
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2023/6/6
 * Time：14:16
 */
class YouPinApi extends Model
{
    public static $fields = [
        'boxRecordId' => 'youpin_query_box_record_id_',
        'orderSubStatus' => [
            1403 => '客服取消订单',
            1404 => '发送报价超时',
            1405 => '报价发送失败，系统取消订单',
            1406 => '买家取消订单，卖家未发货',
            1407 => '确认报价超时',
            1409 => '报价被修改',
            1410 => '报价在steam超时',
            1411 => '报价在Steam令牌取消',
            1412 => '拒绝报价',
            1413 => '取消报价',
            1414 => '商品缺失',
        ]
    ];

    /**
     * 在售查询
     * @return LengthAwarePaginator
     */
    public function paginate()
    {
        $record_id = request()->get('record_id');
        $page = request()->get('page', 1);
        $per_page = request()->get('per_page', 20);

        $record = BoxRecord::find($record_id);
        $skins = Skins::find($record->skin_id);
        if (!$skins || empty($skins->template_id)) {
            return admin_error('饰品被删除', '饰品数据库内未找到该饰品信息！');
        }
        $res = YouPinService::goodsQuery($skins->template_id, $per_page, $page);
        if ($res['code'] !== 0) {
            admin_error($res['msg']);
            $data = [];
        } else {
            $data = $res['data'];
            if (empty($data)) {
                admin_warning('ZBT没有在售', '没有在ZBT平台找到此装备在售信息。');
            }
        }
        foreach ($data as $k => $v) {
            $data[$k]['imageUrl'] = config('filesystems.disks.common.url') . '/' . $skins->image_url;
            $data[$k]['bean'] = $skins->bean;
        }
        \Cache::put(self::$fields['boxRecordId'] . $record_id, $data, 1800);
        $on_sales = static::hydrate($data);
        $paginator = new LengthAwarePaginator($on_sales, $per_page, $per_page);
        $paginator->setPath(url()->current());
        return $paginator;
    }

    /**
     * @param array|string $relations
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function with($relations)
    {
        return new static;
    }

    /**
     * @param $key
     * @return |null
     */
    public static function findOrFail($key)
    {
        return null;
    }

    public function getKeyName()
    {
        return 'id';
    }
}
