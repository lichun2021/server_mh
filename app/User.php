<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *charge-rebate
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * 换成KEY
     * @var array
     */
    public static $cacheKey = [
        'WarLoss' => 'war_loss:user_id_'
    ];

    /**
     * 头像
     * @param $value
     * @return string
     */
    public function getAvatarAttribute($value)
    {
        $preg = "/^http(s)?:\\/\\/.+/";
        if(preg_match($preg,$value))
        {
            return $value;
        }
        return config('filesystems.disks.common.url').'/'.$value;
    }

    /**
     * <访问器> 充值累计返利总计
     *
     * @param [type] $value
     * @return string
     */
    public function getChargeRebateAttribute()
    {
        return UserRewardLog::query()->where('user_id',$this->id)->where('type',4)->sum('bean');
    }

    /**
     * <访问器> 个人总计 包含首充奖励/累积充值返利/充值解冻金豆
     * @return mixed
     */
    public function getPersonalTotalAttribute()
    {
        return UserRewardLog::query()->where('user_id',$this->id)->where(function ($query)
        {
            return $query->where('type',3)->orWhere('type',4)->orWhere('type',5);
        })->sum('bean');
    }

    /**
     * <访问器> 充值累计返利总计
     *
     * @param [type] $value
     * @return string
     */
    public function getPromotionTotalAttribute()
    {
        return UserRewardLog::query()->where('user_id',$this->id)->where(function ($query)
        {
            return $query->where('type',1)->orWhere('type',2);
        })->sum('bean');
    }

    /**
     * 关联上级
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id', 'id');
    }

    /**
     * 用户Tags
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(UserTagList::class,'user_tags','user_id','tag_id');
    }

    /**
     * 关联百度渠道
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function baiduChannel()
    {
        return $this->belongsTo(BaiduChannel::class, 'baidu_channel_id', 'id');
    }
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

}
