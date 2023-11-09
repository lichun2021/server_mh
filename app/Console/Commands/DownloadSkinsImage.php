<?php

namespace App\Console\Commands;

use App\Admin\Actions\User\Log;
use App\Skins;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;

class DownloadSkinsImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download-skins-image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '下载饰品图片保存到本地';

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
        Skins::select(['id', 'cover', 'image_url'])->orderByDesc('id')->chunk(100, function ($skins) {
            foreach ($skins as $item) {
                try {
                    if (empty($item->image_url)) {
                        $cover = $item->cover;
                        $basePath = parse_url($cover);
                        $urlInfo = pathinfo($basePath['path']);
                        if (isset($urlInfo['extension'])) {
                            //有后缀
                            $fileName = $urlInfo['basename'];
                        } else {
                            //无后缀
                            $fileName = $urlInfo['basename'] . '.png';
                        }
                        $urlPath = 'images/skins/' . $fileName;
                        $filePath = public_path('uploads') . '/' . $urlPath;
                        $response = Http::withOptions([
                            'save_to' => $filePath
                        ])->get($item->cover);
                        if ($response->getStatusCode() == 200) {
                            $item->image_url = $urlPath;
                            $item->save();
                            echo 'OK' . PHP_EOL;
                        } else {
                            \Log::error('图像下载失败', [$item->id]);
                            echo 'Fail' . PHP_EOL;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('图像下载失败', [$item->id]);
                    echo 'Fail' . PHP_EOL;
                }

            }
        });
        return 0;
    }
}
