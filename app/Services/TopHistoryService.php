<?php


namespace App\Services;

use App\BoxRecord;
use Illuminate\Support\Facades\Redis;
class TopHistoryService
{
    public static $cacheKey = 'box_top_history';

    /**
     * 输出
     * @return mixed
     */
    public static function run()
    {
        $res = Redis::lrange(self::$cacheKey,0,39);
        if (empty($res)){
            self::initHistory();
            return self::run();
        }
        $data = implode(',',$res);
        $data = '['.$data.']';
        return json_decode($data,true);
    }

    /**
     * 处理数据
     * @param $ids
     */
    public static function handle($ids)
    {
        $query = BoxRecord::with(['get_user:id,name,avatar','box' => function($query){
            return $query->select(['id', 'name', 'intact_cover']);
        },'skins' => function($query){
            return $query->select(['id','dura','rarity']);
        }])->select(['id', 'get_user_id', 'box_id','skin_id', 'box_name', 'box_bean', 'name', 'cover', 'dura', 'lv', 'bean', 'type'])
            ->whereIn('id', $ids);

        $box_records = $query->get();
        $box_records->append(['profit_ratio', 'lv_alias', 'lv_bg_image', 'dura_alias', 'type_alias']);
        $box_records = $box_records->toArray();
        self::push($box_records);
    }

    /**
     * 加入列表
     * @param array $records
     */
    public static function push($records = [])
    {
        $data = [];
        foreach ($records as $record){
            /*if ($record['type'] == 3){
                \Log::error('对战武器获得用户:'.$record['get_user_id'].' 饰品：'.$record['name']. ' 价格：'.$record['bean']);
            }*/
            $data[] = json_encode($record);
        }
        Redis::lpush(self::$cacheKey,...$data);
        Redis::ltrim('box_top_history',0,99);
    }

    /**
     * 初始化记录数据
     */
    private static function initHistory()
    {
        $query = BoxRecord::with(['get_user:id,name,avatar','box' => function($query){
            return $query->select(['id', 'name', 'intact_cover']);
        },'skins' => function($query){
            return $query->select(['id','dura','rarity']);
        }])->select(['id', 'get_user_id', 'box_id','skin_id', 'box_name', 'box_bean', 'name', 'cover', 'dura', 'lv', 'bean', 'type'])
            ->whereIn('type', [1, 2, 3, 4, 5, 6]);

        $box_records = $query->orderBy('id', 'DESC')->limit(40)->get();
        $box_records->append(['profit_ratio', 'lv_alias', 'lv_bg_image', 'dura_alias', 'type_alias']);
        $box_records = $box_records->toArray();
        self::push($box_records);
    }
}
