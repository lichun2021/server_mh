<?php


namespace App\Admin\Actions\Stars;

use App\Skins;
use App\StarsContain;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Actions\RowAction;

class AddContain extends RowAction
{
    public $name = '新增奖项';

    public function handle(Model $model)
    {
        try {
            $skin_id = request()->post('skin_id');
            $skins = Skins::find($skin_id);
            if (!$skins){
                throw new \Exception('添加饰品不存在！');
            }
            $contain = new StarsContain();
            $contain->stars_id= $model->id;
            $contain->skin_id = $skin_id;
            $contain->save();
            return $this->response()->success('添加成功')->refresh();
        } catch (\Exception $e){
            return $this->response()->error('失败：'.$e->getMessage());
        }


    }

    public function form()
    {
        $this->select('skin_id', '饰品')->options(function ($id) {
            $skins = Skins::find($id);
            if ($skins) {
                return [$skins->id => $skins->name . ' (' . Skins::$fields['dura'][$skins->dura] . ')'];
            }
        })->ajax('/' . config('admin.route.prefix') . '/api/skins')->required();
    }
}
