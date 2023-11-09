<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/31 18:31
 */

namespace App\Admin\Actions\Delivery;

use App\BoxRecord;
use Encore\Admin\Actions\RowAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class SendBack extends RowAction
{
    public $name = '退回申请';

    public function handle(Model $model)
    {
        DB::transaction(function () use ($model) {
            $record = BoxRecord::query()->where('id', $model->id)->lockForUpdate()->first();
            $record->back_message = request()->post('back_message');
            $record->status = 0;
            $record->save();
        });

        return $this->response()->success('成功退回玩家仓库！')->refresh();
    }

    public function form()
    {
        $this->textarea('back_message', '原因')->placeholder('填写退回原因。')->rules(['required','max:255'],['required' => '退回原因不能为空','max' => '做多支持255个字符']);
    }
}
