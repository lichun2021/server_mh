<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class BoxRecord extends Model
{
    /**
     * @var array 20201014更新去除隐藏
     */
    //protected $hidden = ['uuid'];

    protected $appends = ['dura_alias', 'lv_alias', 'lv_bg_image'];

    /**
     * @var array AwardLevel
     */
    protected static $award_levels = null;

    /**
     * 字段映射
     *
     * @var array
     */
    public static $fields = [
        'status' => [
            0 => '待操作',
            1 => '领取成功', //领取到Steam账户
            2 => '回收完成',
            3 => '冻结中',
            4 => '申请提货',
            5 => '正在发货',
            6 => '请去steam收货'
        ],
        'type' => [
            0 => '其他',
            1 => '盲盒开箱',
            2 => '福利宝箱',
            3 => '盲盒对战',
            4 => '幸运饰品',
            5 => '饰品商城',
            6 => '夺宝',
            7 => '装备合成',
            8 => '红星轮盘'
        ],
        'cacheKey' => 'box_history_list_',
    ];

    /**
     * <访问器> 利润比
     * @return string
     */
    public function getProfitRatioAttribute()
    {
        if($this->box_bean == 0){
            return null;
        }
        $divide = bcdiv($this->bean,$this->box_bean,2);
        $multiply = bcmul($divide,100,2);
        return $multiply > 100 ? $multiply.'%':null;
    }

    /**
     * <访问器> 英文名称
     * @return string
     */
    public function getItemNameAttribute()
    {
        if($this->dura != 0){
            return $this->name . ' ('. Skins::$fields['dura'][$this->dura] .')';
        }
        return $this->name;
    }

    /**
     * <访问器> 外观名称
     * @return string
     */
    public function getDuraAliasAttribute()
    {
        return Skins::$fields['dura'][$this->dura] ?? '';
    }

    /**
     * <访问器> 类型
     * @return string
     */
    public function getTypeAliasAttribute()
    {
        return static::$fields['type'][$this->type] ?? '';
    }

    /**
     * <访问器> 品质名称
     *
     * @return string
     */
    public function getLvAliasAttribute()
    {
        if (self::$award_levels === null){
            self::$award_levels = SkinsLv::getList();
        }
        return self::$award_levels[$this->lv]['name'] ?? null;
    }

    /**
     * <访问器> 背景图片
     *
     * @return string
     */
    public function getLvBgImageAttribute()
    {
        if ($this->lv <= 0){
            return null;
        }
        return config('filesystems.disks.common.url').'/'.self::$award_levels[$this->lv]['bg_image'] ?? '';
    }

    /**
     * 状态名称
     *
     * @return string
     */
    public function getStatusAliasAttribute()
    {
        return static::$fields['status'][$this->status] ?? '';
    }

    /**
     * <访问器> 封面图
     *
     * @param [type] $value
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
     * 关联饰品获得者
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function get_user()
    {
        return $this->belongsTo(User::class, 'get_user_id', 'id');
    }

    /**
     * 关联饰品持有者
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联宝箱
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function box()
    {
        return $this->belongsTo(Box::class, 'box_id', 'id');
    }

    /**
     * 关联饰品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skins()
    {
        return $this->belongsTo(Skins::class, 'skin_id', 'id');
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
