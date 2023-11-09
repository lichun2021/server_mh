<?php


namespace App\Admin\Actions\User;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use App\User;

class BindSuperior extends RowAction
{
    public $name = '绑定上级';

    public function handle(Model $model)
    {
        $invite_code = request()->post('invite_code');
        $inviter = User::query()->where(['invite_code' => $invite_code])->first();
        if (!$inviter){
            return $this->response()->error('推广码不存在！')->refresh();
        }

        if ($model->id === $inviter->id){
            return $this->response()->error('绑定失败，上级用户不能为自己！')->refresh();
        }
        
        $model->inviter_id = $inviter->id;
        if ($model->save()) {
            return $this->response()->success('上级绑定成功！')->refresh();
        }

        return $this->response()->error('上级绑定失败！')->refresh();
    }

    public function form()
    {
        $this->text('invite_code', __('上级推广码'))->required();
    }

}
