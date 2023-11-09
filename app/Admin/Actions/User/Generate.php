<?php


namespace App\Admin\Actions\User;

use App\User;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;

class Generate extends Action
{
    public $name = '生成账号';

    protected $selector = '.import-post';

    public function handle(Request $request)
    {
        $num = $request->post('num');
        $data = '';
        try {
            DB::transaction(function () use ($num,&$data) {
                for ($i = 0; $i < $num; $i++) {
                    $mobile = '12'.random_int(100000000,999999999);
                    $name = $this->rand(5);
                    $password = $this->rand(6);
                    $user = new User();
                    $user->mobile = $mobile;
                    $user->name = $name;
                    $user->invite_code = md5($mobile . $name . $password);
                    $user->password = password_hash($password, PASSWORD_DEFAULT);
                    if ($user->save()) {
                        $invite_code = 'C' . getInviteCode($user->id);
                        $user->invite_code = $invite_code;
                        $default_avatar = ['default_avatar/1.jpg', 'default_avatar/2.jpg', 'default_avatar/3.jpg', 'default_avatar/4.jpg','default_avatar/5.jpg', 'default_avatar/6.jpg', 'default_avatar/7.jpg', 'default_avatar/8.jpg', 'default_avatar/9.jpg', 'default_avatar/10.jpg', 'default_avatar/11.jpg', 'default_avatar/12.jpg', 'default_avatar/13.jpg', 'default_avatar/14.jpg', 'default_avatar/15.jpg', 'default_avatar/16.jpg', 'default_avatar/17.jpg', 'default_avatar/18.jpg', 'default_avatar/19.jpg', 'default_avatar/20.jpg', 'default_avatar/21.jpg', 'default_avatar/22.jpg', 'default_avatar/23.jpg', 'default_avatar/24.jpg', 'default_avatar/25.jpg', 'default_avatar/26.jpg', 'default_avatar/27.jpg', 'default_avatar/28.jpg', 'default_avatar/29.jpg', 'default_avatar/30.jpg'];
                        $key = array_rand($default_avatar, 1);
                        $user->avatar = $default_avatar[$key];
                        $user->save();
                    }
                    $data .= '账号：'.$mobile.'    密码：'.$password.PHP_EOL;
                }
            });
            $fileName = time().'.txt';
            $path = public_path('uploads').'/temp/'.$fileName;
            file_put_contents($path,$data);
            return $this->response()->download(config('app.url').'/uploads/temp/'.$fileName);
        } catch (\Exception $e) {
            return $this->response()->error('错误：' . $e->getMessage());
        }
    }

    public function form()
    {
        $this->integer('num', '数量')->rules(['required', 'integer', 'max:100'])->help('要生成的账号数量');
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-post"><i class="fa fa-plus"></i> 生成账号</a>
HTML;
    }


    private function rand($len)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $string = time();
        for (; $len >= 1; $len--) {
            $position = rand() % strlen($chars);
            $position2 = rand() % strlen($string);
            $string = substr_replace($string, substr($chars, $position, 1), $position2, 0);
        }
        return $string;
    }
}
