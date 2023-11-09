<?php

namespace App\Admin\Actions\Card;

use App\Card;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;

class Generate extends Action
{
    public $name = '生成卡密';

    protected $selector = '.import-post';

    public function handle(Request $request)
    {
        $numbers= $request->post('numbers');
        $bean = $request->post('bean');
        try{
            DB::transaction(function () use ($numbers,$bean) {
                $numbers =  explode(PHP_EOL,$numbers);
                if (empty($numbers) || !is_array($numbers)){
                    throw new \Exception("提交数据错误！");
                }
                $data = [];
                foreach ($numbers as $number){
                    $data[] = ['number' => $number,'bean' => $bean,'created_at' => date('Y-m-d H:i:s')];
                }
                Card::insert($data);
            });
            return $this->response()->success('操作成功！')->refresh();
        }catch (\Exception $e)
        {
            return $this->response()->error('错误：'.$e->getMessage());
        }
    }

    public function form()
    {
        $value = '';
        for ($i = 0;$i < 30;$i++){
            $value .= strtoupper(md5(Str::uuid())).PHP_EOL;
        }
        $this->integer('bean','R币')->required()->help('请注意必须对应金豆列表M豆,如5、10、50，否则将造成卡密无法使用、充值成功！');
        $this->textarea('numbers','卡号')->value(trim($value))->rows(30)->readonly()->help('一定要把内容复制到->卡盟->商品管理->添加卡密里并勾选“添加成功后删除重复卡密”后再点击提交,不提交重新点生产卡密内容不变！卡盟地址：www.miuke.net');
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-post"><i class="fa fa-plus"></i> 生成卡密</a>
HTML;
    }
}
