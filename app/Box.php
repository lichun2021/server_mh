<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class Box extends Model
{
    /**
     * @var string 数据表
     */
    protected $table = 'boxs';


    protected $casts = [
        'luck_interval' => 'array',
        'luck_interval_anchor' => 'array'
    ];

    /**
     * 字段映射
     *
     * @var array
     */
    public static $fields = [
        'is_putaway' => [
            0 => '下架',
            1 => '上架'
        ],
        'cacheKey' => [
            0 => 'box_list', //宝箱下拉缓存
            1 => 'api_cate_box_list', //首页宝箱列表缓存
            2 => 'open_box_lock_id_', //宝箱爆率原子锁Id
            3 => 'open_box_lock_anchor_id_', //宝箱爆率原子锁Id (主播)
            4 => 'open_box_list_id_', //宝箱爆率
            5 => 'open_box_list_anchor_id_', //宝箱爆率（主播）
            6 => 'open_box_lucky_list_id_', //宝箱幸运爆率
            7 => 'open_box_lucky_list_anchor_id_', //宝箱幸运爆率（主播）
            8 => 'box_detail_id_', //宝箱详情缓存
            9 => 'game_arena_box_list', //对战宝箱缓存
            10 => 'hot_box_list', //宝箱下拉缓存
        ]
    ];

    /**
     * <访问器> 宝箱封面图
     *
     * @param [type] $value
     * @return string
     */
    public function getCoverAttribute($value)
    {
        return $value ? config('filesystems.disks.common.url') . '/' . $value : '';
    }

    /**
     * <访问器> 幸运区间
     * @param $value
     * @return string
     * @throws \Exception
     */
    public function getLuckIntervalAttribute($value)
    {
        $value = json_decode($value,true);
        if (is_array($value) && count($value) === 2){
            return $value[0].'/'.$value[1];
        }
        throw new \Exception('宝箱LuckInterval字段解析错误');
    }

    /**
     * <访问器> 幸运区间（主播）
     * @param $value
     * @return string
     * @throws \Exception
     */
    public function getLuckIntervalAnchorAttribute($value)
    {
        $value = json_decode($value,true);
        if (is_array($value) && count($value) === 2){
            return $value[0].'/'.$value[1];
        }
        throw new \Exception('宝箱LuckInterval字段解析错误');
    }

    /**
     * <访问器> 武器封面图
     *
     * @param [type] $value
     * @return string
     */
    public function getWeaponCoverAttribute($value)
    {
        return $value ? config('filesystems.disks.common.url') . '/' . $value : '';
    }

    /**
     * <访问器> 武器完整封面图
     *
     * @param [type] $value
     * @return string
     */
    public function getIntactCoverAttribute($value)
    {
        return $value ? config('filesystems.disks.common.url') . '/' . $value : '';
    }
    
    /**
     * 按颜色计算爆率
     * @return array
     */
    public function getOddsListAttribute()
    {
        $oddsList = BoxContain::query()
            ->select(['level',\DB::raw('SUM(odds) as odds')])
            ->where('box_id',$this->id)->groupBy('level')
            ->get()
            ->toArray();
        $totalOdds = array_sum(array_column($oddsList, 'odds'));
        foreach ($oddsList as $key => $item){
            $oddsList[$key]['odds'] = bcdiv($item['odds'] * 100, $totalOdds, 2) . '%';
            $oddsList[$key]['level_alias'] = BoxContain::$fields['lv'][$item['level']];
        }
        return $oddsList;
    }
    
    /**
     * 对战按颜色计算爆率
     * @return array
     */
    public function getGameOddsListAttribute()
    {
        $oddsList = BoxContain::query()
            ->select(['level',\DB::raw('SUM(odds) as odds')])
            ->where(['box_id' => $this->id, 'is_game' => 1])->groupBy('level')
            ->get()
            ->toArray();
        $totalOdds = array_sum(array_column($oddsList, 'odds'));
        foreach ($oddsList as $key => $item){
            $oddsList[$key]['odds'] = $item['odds'] > 0 ? bcdiv($item['odds'] * 100, $totalOdds, 2) . '%':'0.00%';
            $oddsList[$key]['level_alias'] = BoxContain::$fields['lv'][$item['level']];
        }
        return $oddsList;
    }
    
    /**
     * 包含物品
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contains()
    {
        return $this->hasMany(BoxContain::class, 'box_id', 'id');
    }

    /**
     * 宝箱下拉
     * @return mixed
     */
    public static function getList()
    {
        $key = self::$fields['cacheKey'][0];
        $list = Cache::get($key);
        if ($list === null){
            $list = self::pluck('name','id')->toArray();
            Cache::put($key,$list);
        }
        return $list;
    }

    /**
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model){
            $isUse = BoxContain::where(['box_id' => $model->id])->exists();
            if ($isUse){
                throw new \Exception('宝箱内包含饰品，无法删除！');
            }
            return true;
        });
    }

    /**
     * 格式化时间
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
