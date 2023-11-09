<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class BeanChangeRecord extends Model
{
    /**
     * @var array
     */
    protected $appends = ['type_alias', 'change_type_alias'];

    /**
     * @var array 字段映射
     */
    public static $fields = [
        'type' => [
            0 => '支出',
            1 => '收入'
        ],
        'change_type' => [
            1 => '开箱',
            2 => '盲盒对战',
            3 => '幸运饰品',
            4 => '装备回收',
            5 => '充值',
            6 => '首充奖励',
            7 => '被邀充值收益',
            8 => '邀请注册奖励',
            9 => '盲盒对战均分奖励',
            10 => '盲盒对战失败安慰奖',
            11 => '红包',
            12 => '幸运夺宝',
            13 => '注册绑定Steam交易链接赠送',
            14 => '饰品商城',
            15 => '时来运转',
            16 => 'VIP充值奖励',
            17 => 'VIP升级红包',
            18 => '签到任务完成奖励',
            19 => '每日经典盲盒花费100T币任务',
            20 => '每日经典盲盒花费300T币任务',
            21 => '每日经典盲盒花费1000T币任务',
            22 => '每日经典盲盒花费5000T币任务',
            23 => '注册赠送',
            24 => '每日盲盒对战花费100T币任务',
            25 => '每日盲盒对战花费300T币任务',
            26 => '每日盲盒对战花费1000T币任务',
            27 => '每日盲盒对战花费5000T币任务',
            28 => '每日追梦花费100T币任务',
            29 => '每日追梦花费300T币任务',
            30 => '每日追梦花费1000T币任务',
            31 => '每日追梦花费5000T币任务',
        ]
    ];

    /**
     * 添加记录
     * @param $type
     * @param $change_type
     * @param $bean
     * @return bool
     */
    public static function add($type,$change_type,$bean,$user_id = false)
    {
        if ($user_id === false){
            $uid = auth('api')->id();
        } else {
            if (empty($user_id)){
                throw new \Exception('收支记录未找到用户Id,请联系管理员基于处理！');
            }
            $uid = $user_id;
        }
        $model = new self();
        $model->user_id = $uid;
        $model->final_bean = User::find($uid)->bean;
        $model->type = $type;
        $model->change_type = $change_type;
        $model->bean = $bean;
        return $model->save();
    }

    /**
     * <访问器> 流水类型
     * @return string
     */
    public function getTypeAliasAttribute()
    {
        return self::$fields['type'][$this->type];
    }

    /**
     * <访问器> 流水类型
     * @return mixed
     */
    public function getChangeTypeAliasAttribute()
    {
        return self::$fields['change_type'][$this->change_type];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
