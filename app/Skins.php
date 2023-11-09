<?php


namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

/**
 * 饰品
 * Class Skins
 * @package App
 * @author 春风 <860646000@qq.com>
 */
class Skins extends Model
{
    /**
     * 数据表
     * @var string
     */
    protected $table = 'skins';

    /*protected $casts = [
        'luck_interval' => 'array',
        'luck_interval_anchor' => 'array'
    ];*/

    /**
     * @var array
     */
    public static $fields = [
        //外观
        'dura' => [
            0 => '无',
            1 => '崭新出厂',
            2 => '略有磨损',
            3 => '久经沙场',
            4 => '破损不堪',
            5 => '战痕累累',
            6 => '无涂装'
        ],
        //品质
        'lv' => [
            1 => '金',
            2 => '红',
            3 => '紫',
            4 => '蓝',
            5 => '灰'
        ],
        'rarity' => [
            //0 => null,
            1 => 'rarity_common_weapon',//消费级
            2 => 'rarity_uncommon_weapon',//工业级
            3 => 'rarity_rare_weapon',//军规级
            4 => 'rarity_mythical_weapon',//受限
            5 => 'rarity_legendary_weapon',//保密
            6 => 'rarity_ancient_weapon',//隐秘
            7 => 'rarity_common',//普通级
            8 => 'rarity_rare',//高级
            9 => 'rarity_ancient',//非凡
            10 => 'rarity_legendary',//奇异
            11 => 'rarity_mythical',//卓越
            12 => 'rarity_contraband',//违禁
            13 => 'rarity_rare_character',//探员:高级
            14 => 'rarity_mythical_character',//探员:卓越
            15 => 'rarity_legendary_character',//探员:非凡
            16 => 'rarity_ancient_character',//探员:大师
        ],
        'rarity_cn' => [
            0 => '无',
            1 => '消费级',
            2 => '工业级',
            3 => '军规级',
            4 => '受限',
            5 => '保密',
            6 => '隐秘',
            7 => '普通级',
            8 => '高级',
            9 => '非凡',
            10 => '奇异',
            11 => '卓越',
            12 => '违禁',
            13 => '探员:高级',
            14 => '探员:卓越',
            15 => '探员:非凡',
            16 => '探员:大师',
        ],
        'cacheKey' => [
            0 => 'skins_list', //饰品下拉菜单缓存
            1 => 'lucky_list_type_id_', //幸运开箱分类列表
            2 => 'lucky_open_box_lock_id_', //幸运开箱原子锁
            3 => 'lucky_open_box_anchor_lock_id_', //幸运开箱原子锁（主播）
            4 => 'luck_open_box_interval_id_', //幸运开箱缓存
            5 => 'luck_open_box_interval_anchor_id_', //幸运开箱缓存（主播）
        ]
    ];

    /**
     * @var array
     */
    protected $appends = ['dura_alias'];

    /**
     * <访问器> 外观名称
     *
     * @return mixed
     */
    public function getDuraAliasAttribute()
    {
        return static::$fields['dura'][$this->dura];
    }

    /**
     * <访问器> 品质名称
     *
     * @return string
     */
    public function getLvAliasAttribute()
    {
        return static::$fields['lv'][$this->lv];
    }

    /**
     * <访问器> 封面图
     * @param $value
     * @return string
     */
    public function getCoverAttribute($value)
    {
        if (getConfig('is_c5_image_cdn') == 1 && filter_var($value, FILTER_VALIDATE_URL) !== false){
            return $value;
        }
        $basePath = parse_url($value);
        $urlInfo = pathinfo($basePath['path']);
        if (isset($urlInfo['extension'])){
            //有后缀
            $fileName = $urlInfo['basename'];
        } else {
            //无后缀
            $fileName = $urlInfo['basename'].'.png';
        }
        return config('filesystems.disks.common.url') .'/images/skins/'.$fileName;
    }

    /**
     * <访问器> 等级背景图片
     *
     * @return string
     */
    public function getLvBgImageAttribute()
    {
        $lvList = SkinsLv::getList();
        return config('filesystems.disks.common.url').'/'.$lvList[$this->lv]['bg_image'] ?? '';
    }

    /**
     * <访问器> 稀有程度
     *
     * @return string
     */
    public function getRarityAliasAttribute()
    {
        return self::$fields['rarity_cn'][$this->rarity];
    }

    /**
     * <访问器> 幸运区间
     * @param $value
     * @return string
     */
    public function getLuckIntervalAttribute($value)
    {
        $value = json_decode($value,true);
        return is_array($value) && count($value) >=2 ? $value[0].'/'.$value[1]: '';
    }

    /**
     * <修改器> 幸运区间
     * @param $value
     * @throws \Exception
     */
    public function setLuckIntervalAttribute($value)
    {
        $value = explode('/',$value);
        if (!is_array($value) || count($value) < 2 || $value[0] >= $value[1]){
            throw new \Exception('幸运区间值错误！');
        }
        $this->attributes['luck_interval'] = json_encode($value);
    }

    /**
     * <访问器> 幸运区间（主播）
     * @param $value
     * @return string
     */
    public function getLuckIntervalAnchorAttribute($value)
    {
        $value = json_decode($value,true);
        return is_array($value) && count($value) >=2 ? $value[0].'/'.$value[1]: '';
    }

    /**
     * <修改器> 幸运区间（主播）
     * @param $value
     * @throws \Exception
     */
    public function setLuckIntervalAnchorAttribute($value)
    {
        $value = explode('/',$value);
        if (!is_array($value) || count($value) < 2 || $value[0] >= $value[1]){
            throw new \Exception('幸运区间（主播）值错误！');
        }
        $this->attributes['luck_interval_anchor'] = json_encode($value);
    }

    /**
     * 随机生成幸运值
     * @param $value
     * @return string
     * @throws \Exception
     */
    public function generateInterval($value)
    {
        $luck_array = explode('/', $value);

        if (!is_array($luck_array ) || count($luck_array) !== 2 || $luck_array[0] >= $luck_array[1]){
            throw new \Exception('幸运饰品系统错误！');
        }

        $luck_rand_num = mt_rand($luck_array[0], $luck_array[1]);
        return bcmul($this->bean,bcdiv($luck_rand_num,100,2),2);
    }

    /**
     * 饰品下拉
     * @return array|mixed|null
     */
    public static function getList()
    {
        $key = self::$fields['cacheKey'][0];
        $list = Cache::get($key);
        if ($list === null){
            $list = [];
            $data = self::select(['id', 'name', 'dura'])->get()->toArray();
            foreach ($data as $item){
                if ($item['dura'] == 0){
                    $list[$item['id']] = $item['name'];
                } else {
                    $list[$item['id']] = $item['name'].' ('. Skins::$fields['dura'][$item['dura']] .')';
                }
            }
            Cache::put($key,$list);
        }
        return $list;
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

    /**
     * 注册事件
     */
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($model){
            $isUse = BoxContain::where(['skin_id' => $model->id])->exists();
            if ($isUse){
                throw new \Exception('有投放此饰品的宝箱，无法删除！');
            }
            return true;
        });
    }
}

