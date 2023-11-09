<?php

namespace App\Admin\Forms\Settings;

use App\SystemConfig as Config;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Form;

class RobotConfig extends Form
{
    /**
     * 表单标题.
     *
     * @var string
     */
    public $title = '机器人配置';

    /**
     * 表单填充数据
     *
     * @var array
     */
    private $dataArray;

    /**
     * 所属配置
     *
     * @var int
     */
    private $tab = 3;

    /**
     * Handle the form request.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request)
    {
        $data = $request->post();
        foreach ($data as $key => $value) {
            if ($value == 'on' || $value == 'off') {
                $value = $value == 'on' ? 1 : 0;
                Config::query()->where('code', $key)->update(['value' => $value]);
            } else {
                Config::query()->where('code', $key)->update(['value' => $value]);
            }
        }
        $files = $request->file();
        foreach ($files as $key => $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $fileName = 'images/' . date('Ym') . '/' . date('d') . '/' . date('YmdHis') . rand(10000, 99999) .'.'. $file->extension();
                $bool = Storage::disk('common')->put($fileName,file_get_contents($file->getRealPath()));
                if (!$bool){
                    throw new \Exception('文件保存失败！');
                }
                Config::query()->where('code', $key)->update(['value' => $fileName]);
            }
        };
        \Cache::delete(getCacheKey('config'));
        admin_toastr('网站配置更新成功!');
        return back();
    }

    /**
     * Build a form here.
     */

    public function form()
    {

        $configs = Config::query()->where('tab', $this->tab)->get()->toArray();
        foreach ($configs as $config) {
            $type = $config['type'];
            $this->dataArray[$config['code']] = $config['value'];
            $states = [
                'on'  => ['value' => 1, 'text' => '打开', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
            ];
            switch ($type) {
                case 'switch':
                    $this->switch($config['code'], $config['name'])->states($states)->help($config['description']);
                    break;
                case 'number':
                    $this->number($config['code'], $config['name'])->help($config['description']);
                    break;
                case 'rate':
                    $this->rate($config['code'], $config['name'])->help($config['description']);
                    break;
                case 'textarea':
                    $this->textarea($config['code'], $config['name'])->help($config['description']);
                    break;
                case 'file':
                    $this->file($config['code'], $config['name'])->help($config['description']);
                    break;
                case 'select':
                    $this->select($config['code'], $config['name'])->options(json_decode($config['scope'], true))->help($config['description']);
                    break;
                default:
                    $this->text($config['code'], $config['name'])->help($config['description']);
            }
        }
    }

    /**
     * The data of the form.
     *
     * @return array $data
     */
    public function data()
    {
        return $this->dataArray;
    }
}
