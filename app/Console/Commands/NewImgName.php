<?php

namespace App\Console\Commands;

use App\Skins;
use Illuminate\Console\Command;

class NewImgName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new-img-name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '饰品图片更名';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = 0;
        $count2 = 0;
        Skins::where('image_url', 'like', '%-9a81dlWLwJ2UUGcVs%')->select(['id', 'cover', 'image_url'])->orderByDesc('id')->chunk(100, function ($skins) use (&$count,&$count2) {
            foreach ($skins as $item) {
                $filePath = public_path('uploads') . '/' . $item->image_url;
                $pathInfo = pathinfo($filePath);

                if (isset($pathInfo['extension'])){
                    continue;
                } else {
                    $fileName = $pathInfo['filename'].'.png';
                }

                if (is_file($filePath)){
                    $count++;
                    //文件存在
                    $rename = $pathInfo['dirname'].'/'.$fileName;
                    //改名
                    rename($filePath,$rename);
                }
                $count2++;
                $item->image_url = 'images/skins/'.$fileName;
                $item->save();
            }

        });
        echo '文件改名次数：'.$count.PHP_EOL;
        echo '数据路径更改次数：'.$count2;
        return 0;
    }
}
