<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/22 10:07
 */

namespace App\Admin\Actions;

use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class ClearCache extends Action
{
    protected $selector = '.clear-cache';

    public function handle(Request $request)
    {
        if (auth('admin')->id() !== 1){
            return $this->response()->error('权限不足');
        }
        Cache::flush();
        return $this->response()->success('清理完成')->refresh();
    }

    public function dialog()
    {
        $this->confirm('确认清除缓存','清空缓存开箱物品列表将重新生成,影响正常利润计算  请慎重使用！');
    }

    public function html()
    {
        return <<<HTML
<li>
    <a class="clear-cache" href="#">
      <i class="fa fa-trash"></i>
      <span>清理缓存</span>
    </a>
</li>
HTML;
    }
}
