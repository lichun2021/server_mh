<?php

namespace App\Admin\Actions\Room;

use App\RoomAward;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class AssignAward extends RowAction
{
    public $name = '指定奖品';

    public function handle(Model $model)
    {
        $award_id = request()->post('award_id');
        \DB::beginTransaction();
        try {
            RoomAward::where(['room_id' => $model->room_id, 'designated_user' => $model->user_id])->update(['designated_user' => 0]);

            $roomAward = RoomAward::where(['id' => $award_id])->lockForUpdate()->first();
            $roomAward->designated_user = $model->user_id;
            $roomAward->save();
            \DB::commit();
            return $this->response()->success('操作成功！')->refresh();
        }catch (\Exception $e){
            \DB::rollBack();
            return $this->response()->error('操作失败：'.$e->getMessage());
        }
    }

    public function form()
    {
        $room_id = request()->get('room_id');
        $this->select('award_id', '用户')->options('/'.config('admin.route.prefix').'/api/room/awards?room_id='.$room_id)->required();
    }
}
