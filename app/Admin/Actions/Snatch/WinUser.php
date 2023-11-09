<?php


namespace App\Admin\Actions\Snatch;

use App\SnatchUser;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Actions\RowAction;
class WinUser extends  RowAction
{
    public $name = '指定获胜';

    public function handle(Model $model)
    {
        if (request()->post('win_user_id') > 0){
            $model->win_user_id = request()->post('win_user_id');
        }
        if ($model->save()) {
            return $this->response()->success('操作成功')->refresh();
        }

        return $this->response()->error('操作失败')->refresh();
    }

    public function form()
    {
        $snatchUser = SnatchUser::query()->where('snatch_id',$this -> getKey())->pluck('user_id');
        $user = User::query()->whereIn('id',$snatchUser)->pluck('name','id');
        $this->select('win_user_id', '名称')->options($user)->required();
    }
}
