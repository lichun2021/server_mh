<?php

namespace App\Console\Commands;

use App\User;
use App\Room;
use App\RoomUser;
use App\BoxRecord;
use App\RoomAward;
use App\RoomRecord;
use Illuminate\Console\Command;

class RoomSettlement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'room-settlement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '福利房结算';

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
     */
    public function handle()
    {
        Room::where('status', 0)->where('end_time', '<', now())->chunk(10, function ($rooms) {
            \DB::transaction(function () use ($rooms) {
                $ids = [];
                foreach ($rooms as $room) {
                    $ids[] = $room->id;
                    //查看所有的奖品
                    $box_record_ids = RoomAward::where('room_id', $room->id)->orderBy('id', 'DESC')->pluck('box_record_id')->toArray();

                    //查看所有人数
                    $user_ids = RoomUser::where('room_id', $room->id)->pluck('user_id')->toArray();

                    if (count($user_ids) < 1) {
                        //没有人参与物品还给房主
                        BoxRecord::whereIn('id', $box_record_ids)->update(['status' => 0]);
                    } else {
                        //增加参与记录
                        foreach ($user_ids as $user_id) {
                            $room_record = new RoomRecord();
                            $room_record->user_id = $user_id;
                            $room_record->room_id = $room->id;
                            $room_record->save();
                        }

                        //分发奖品
                        $user_id_array = $user_ids;
                        //优先分发指定
                        foreach ($box_record_ids as $key => $box_record_id) {
                            $is_designated_user = RoomAward::where(['room_id' => $room->id, 'box_record_id' => $box_record_id])->first();
                            if ($is_designated_user->designated_user > 0) {
                                //删除装备
                                unset($box_record_ids[$key]);
                                //获得用户KEY
                                $key = array_search($is_designated_user->designated_user, $user_id_array);
                                if ($key !== false) {
                                    //删除指定用户
                                    unset($user_id_array[$key]);
                                }
                                $user_id = $is_designated_user->designated_user;
                                //更新物品获得者
                                RoomAward::where('box_record_id', $box_record_id)->update([
                                    'get_user_id' => $user_id,
                                ]);
                                //更新仓库物品状态
                                $box_record = BoxRecord::query()->where('id', $box_record_id)->lockForUpdate()->first();
                                //减去战损
                                User::where('id', $user_id)->decrement('loss', $box_record->bean);

                                $box_record->user_id = $user_id;
                                $box_record->status = 0;
                                $box_record->save();
                            }
                        }
                        //分发未指定
                        foreach ($box_record_ids as $box_record_id) {
                            $is_designated_user = RoomAward::where(['room_id' => $room->id, 'box_record_id' => $box_record_id])->first();
                            if ($is_designated_user->designated_user === 0) {
                                //如果奖品大于人数剩下的物品还给房主
                                if (empty($user_id_array)) {
                                    $user_id_array[] = $room->user_id;
                                }
                                //抽奖
                                $key = array_rand($user_id_array, 1);
                                $user_id = $user_id_array[$key];
                                //删除已中将用户ID
                                unset($user_id_array[$key]);
                                //更新物品获得者
                                RoomAward::where('box_record_id', $box_record_id)->update([
                                    'get_user_id' => $user_id,
                                ]);
                                //更新仓库物品状态
/*                                BoxRecord::where('id', $box_record_id)->update([
                                    'user_id' => $user_id,
                                    'status' => 0
                                ]);*/
                                $box_record = BoxRecord::where('id', $box_record_id)->lockForUpdate()->first();
                                //减去战损
                                User::where('id', $user_id)->decrement('loss', $box_record->bean);

                                $box_record->user_id = $user_id;
                                $box_record->status = 0;
                                $box_record->save();
                            }
                        }
                    }
                }
                Room::whereIn('id', $ids)->update(['status' => 1]);
            });
        });
    }
}
