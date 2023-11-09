<?php
/**
 * Created by ChunBlog.com.
 * User: 春风
 * WeChat: binzhou5
 * QQ: 860646000
 * Date: 2020/10/30
 * Time: 23:08
 */

namespace App;

use App\Services\ZbtService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
//use Illuminate\Support\Facades\Request;
class ZbtApi extends Model
{
    public static $fields = [
        'statusCode' => [
            200 => 'OK',
            1317 => '发生异常购买失败, 无满足条件的在售饰品。',
            1363 => '发生异常购买失败。',
            1367 => '该Steam账号由于过去12小时购买订单存在多次不收货或者拒绝收货行为，系统暂时暂时限制其进行购买。',
            70001 => '余额不足',
            106006 => '用户账号被 vac 所以无法交易',
            106310 => '用户提供的交易链接已失效',
            106505 => '用户 steam 账号不可交易',
            106509 => '用户 steam 库存未公开',
            106516 => '用户启用了 steam 家庭监护导致无法交易',
            106517 => '用户 steam 账号被红锁',
            140005 => '交易链接错误',
        ]
    ];
    
    public static $record_id;
    /**
     * 在售查询
     * @return LengthAwarePaginator
     */
    public function paginate()
    {
        $record_id = request()->get('record_id');
        $page = request()->get('page',1);
        $per_page = request()->get('per_page',20);
        $delivery = request()->get('delivery');

        $record = BoxRecord::find($record_id);
        $skins = Skins::find($record->skin_id);
        if (!$skins){
            return admin_error('饰品被删除','饰品数据库内未找到该饰品信息！');
        }
        $res = ZbtService::sell_list($skins->item_id,$page,$per_page,$delivery);
        if ($res['code'] == 0){
            admin_error($res['message']);
            $data = [];
        }else{
            $data = $res['message']['data']['list'];
            if (empty($data)){
                admin_warning('ZBT没有在售','没有在ZBT平台找到此装备在售信息。');
            }
        }

        $on_sales = static::hydrate($data);

        $paginator = new LengthAwarePaginator($on_sales, $res['message']['data']['total'], $per_page);
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
