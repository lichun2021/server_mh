<?php

namespace App\Admin\Actions\Room;

use App\RoomUser;
use App\User;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BatchIn
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/3/26
 * Time：16:48
 */
class BatchIn extends RowAction
{
    public $name = '批量加人';

    public function handle(Model $model)
    {
        $userIds = request()->post('user_ids');
        if (!is_numeric($userIds)) {
            $userIds = explode(',', $userIds);
            if (count($userIds) < 2) {
                $userIds = explode('，', $userIds[0]);
            }
            if (count($userIds) < 2) {
                return $this->response()->error('输入内容错误！');
            }
            foreach ($userIds as $userId) {
                if (!is_numeric($userId)) {
                    return $this->response()->error('输入内容错误！');
                }
            }
        }
        try {
            \DB::transaction(function () use ($model, $userIds) {
                if ($model->status != 0) {
                    throw new \Exception('房间已结束!', -1);
                } elseif ($model->people_number <= RoomUser::where('room_id', $model->id)->count()) {
                    throw new \Exception('房间参与人数已满!', -1);
                }
                if (is_array($userIds)) {
                    foreach ($userIds as $userId) {
                        $isUser = User::find($userId);
                        $isAddRoom = RoomUser::where('room_id', $model->id)->where('user_id', $userId)->first();
                        if (empty($isAddRoom) && $isUser) {
                            RoomUser::create(['room_id' => $model->id, 'user_id' => $userId]);
                        }
                    }
                } else {
                    $isUser = User::find($userIds);
                    $isAddRoom = RoomUser::where('room_id', $model->id)->where('user_id', $userIds)->first();
                    if (empty($isAddRoom) && $isUser) {
                        RoomUser::create(['room_id' => $model->id, 'user_id' => $userIds]);
                    }
                }
            });
            return $this->response()->success('操作成功！')->refresh();
        } catch (\Exception $e) {
            return $this->response()->error('错误：' . $e->getMessage());
        }
    }

    public function form()
    {
        $this->textarea('user_ids', '用户')
            ->required()
            ->rows(5)
            ->placeholder('请输入用户Id,多个用户使用英文或中文逗号分隔，自动过滤已加入用户和网站不存在的用户。 示例： 1,2,3 或 1，2，3');
    }
}
