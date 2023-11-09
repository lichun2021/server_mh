<?php

namespace App\Admin\Actions\BoxRecord;

use App\Jobs\ProcessBuy;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Buy extends RowAction
{
    public $name = '重新购买';

    public function handle(Model $model)
    {
        $model->status = 0;
        $model->back_message = '';
        $model->save();

        ProcessBuy::dispatch($model)->delay(now()->addSeconds(5));

        return $this->response()->success('操作成功')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定'. $this->name . '？');
    }
}