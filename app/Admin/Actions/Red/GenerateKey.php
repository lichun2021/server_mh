<?php


namespace App\Admin\Actions\Red;

use App\RedKey;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;

class GenerateKey extends Action
{
    public $name = '生成口令';

    protected $selector = '.import-post';

    public function handle(Request $request)
    {
        $num = $request->post('num');
        $quantity = $request->post('quantity');
        $denomination = $request->post('denomination');
        $threshold = $request->post('threshold');
        DB::beginTransaction();
        try {
            $data = [];
            if (!is_numeric($num) || !is_numeric($quantity) || !is_numeric($threshold) || $num < 1 || $quantity < 1 || $quantity > 65535 || $threshold < 0) {
                throw new \Exception('输入有误，请检查！');
            }
            $percentage = explode('/', trim($denomination));
            if (count($percentage) !== 2) {
                throw new \Exception('红包面值输入有误！');
            } else {
                if (!is_numeric($percentage[0]) || !is_numeric($percentage[1])) {
                    throw new \Exception('红包开始或结束值输入错误！');
                }
            }
            $percentage = json_encode($percentage);
            for ($i = 0; $i < $num; $i++) {
                $data[] = ['code' => strtoupper(md5(Str::uuid())), 'threshold' => $threshold, 'denomination' => $percentage, 'quantity' => $quantity, 'created_at' => date('Y-m-d H:i:s')];
            }
            RedKey::insert($data);
            DB::commit();
            return $this->response()->success('操作成功！')->refresh();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->response()->error('错误：' . $e->getMessage());
        }
    }

    public function form()
    {
        $this->integer('num', '数量')->rules(['required']);
        $this->integer('quantity', '可用次数')->rules(['required'])->value(1)->help('口令可使用次数 支持最大值65535');
        $this->text('denomination', '面值')->rules(['required'])->value('0.5/1')->help('输入红包面值 随机面值如：0.5/10 固定面值：0.5/0.5');
        $this->integer('threshold', '充值门槛')->rules(['required'])->value(0)->help('充值门槛 用户充值达到该设定值才可使用 0:表示无门槛');
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-post"><i class="fa fa-plus"></i> 生成口令</a>
HTML;
    }
}
