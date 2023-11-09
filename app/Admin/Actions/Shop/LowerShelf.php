<?php

namespace App\Admin\Actions\Shop;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class LowerShelf
 * @package App\Admin\Actions\Shop
 * @author 春风 <860646000@qq.com>
 */
class LowerShelf extends BatchAction
{
    public $name = '批量下架';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            $model->is_shop = 0;
            $model->save();
        }
        return $this->response()->success('操作成功！')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确定下架？');
    }
}
