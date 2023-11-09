<?php

namespace App\Admin\Actions\Shop;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class UpperShelf extends BatchAction
{
    public $name = '批量上架';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            $model->is_shop = 1;
            $model->save();
        }
        return $this->response()->success('操作成功！')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定上架？');
    }
}
