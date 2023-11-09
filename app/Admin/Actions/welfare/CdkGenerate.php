<?php


namespace App\Admin\Actions\welfare;

use App\Welfare;
use App\WelfareCdk;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
class CdkGenerate extends Action
{
    public $name = '生成CDK';

    protected $selector = '.import-post';

    public function handle(Request $request)
    {
        $num= $request->post('num');
        $welfareId = $request->post('welfare_id');
        try{
            \DB::transaction(function () use ($num,$welfareId) {

                if (!is_numeric($num) || $num < 0){
                    throw new \Exception('请输入大于0的数值！');
                }
                $data = [];
                for ($i = 0;$i < $num;$i++){
                    $data[] = ['key' => strtoupper(Str::uuid()),'created_at' => date('Y-m-d H:i:s'), 'welfare_id' => $welfareId];
                }
                WelfareCdk::insert($data);
            });
            return $this->response()->success('操作成功！')->refresh();
        }catch (\Exception $e)
        {
            return $this->response()->error('错误：'.$e->getMessage());
        }
    }

    public function form()
    {
        $this->select('welfare_id','CDK宝箱')->rules('required',[
            'required' => '请选择CDK活动宝箱！'
        ])->options(Welfare::where(['type' => 4])->pluck('name','id'));
        $this->integer('num','CDK数量')->rules('required|integer|min:1',[
            'required' => '生成CDK数量不能为空！',
            'integer' => '只能输入整数！',
            'min' => '请输入大于0的值！',
        ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-post"><i class="fa fa-plus"></i> 生成</a>
HTML;
    }
}
