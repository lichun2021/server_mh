<?php

namespace App\Console\Commands;

use App\User;
use App\UserOld;
use Illuminate\Console\Command;

class UserSyn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userSyn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '迁移用户';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        UserOld::query()->chunk(100,function ($users){
            foreach ($users as $user){
                $model = new User();
                $model->id = $user->id;
                $model->name = $user->name;
                $model->mobile = $user->mobile;
                $model->email = $user->email;
                $model->invite_code = $user->invite_code;
                $model->avatar = $user->avatar;
                $model->inviter_id = $user->inviter_id;
                $model->bean = $user->bean;
                $model->integral = $user->integral;
                $model->password = $user->password;
                $model->promotion_level = $user->promotion_level;
                $model->vip_level = $user->vip_level;
                $model->is_recharge = $user->is_recharge;
                $model->total_recharge = $user->total_recharge;
                $model->close_gift = $user->close_gift;
                $model->close_gift = $user->close_gift;
                $model->ban_pick_up = $user->ban_pick_up;
                $model->anchor = $user->anchor;
                $model->new_welfare = $user->new_welfare;
                $model->box_key_total = $user->box_key_total;
                $model->merchant = $user->merchant;
                $model->reward_new_user = $user->reward_new_user;
                $model->created_at = $user->created_at;
                $model->updated_at = $user->updated_at;
                $model->save();
            }
        });
        return 0;
    }
}
