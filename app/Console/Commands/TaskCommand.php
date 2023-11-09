<?php

namespace App\Console\Commands;

use App\SystemConfig;
use Illuminate\Console\Command;
use Cache;
use Carbon\Carbon;

/**
 * Class TaskCommand
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * php artisan ue:task --id=%(process_num)02d --max=8
 * Date：2022/3/27
 * Time：5:41
 */
class TaskCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ue:task
        {--id=      : 当前编号}
        {--max=     : 最大线程}
        {--sleep=   : 休眠多少毫秒}
        {--debug=   : 是否调试模式}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var string
     */
    public static $configJson;

    /**
     * @var int
     */
    public static $createTime = 0;

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
     * @return mixed
     */
    public function handle()
    {
        $this->id = $this->option('id') ?? '00';
        $this->max = $this->option('max') ?? 8;
        $this->sleep = $this->option('sleep') ?? 1000000;
        $this->debug = $this->option('debug') ?? false;

        if ($this->id > $this->max) {
            return true;
        }

        while (true) {
            $this->doRun();
        }
    }

    /**
     *
     * @param int $taskId
     * @return boolean
     */
    protected function doRun()
    {
        $lockKey = 'task:15224379706';
        $data = [
            'id' => $this->id,
            'max' => $this->max,
            'time' => (new Carbon)->format('Y-m-d H:i:s.u'),
            'key' => $lockKey,
        ];
        $lock = Cache::lock($lockKey, 10);
        try {
            $lock->block(600);
            $start = microtime(true);
            //代码运行区域开始
            $newConfig = json_encode(self::getConfigAll());
            if ($newConfig !== self::$configJson) {
                getConfigAll(true);
                self::$configJson = $newConfig;
            }
            if (getConfig('is_game_arena_bot')) {
                self::$createTime++;
                if (getConfig('game_arena_bot_create_time') <= self::$createTime) {
                    self::$createTime = 0;
                    $this->call('game-arena-bot');

                }
                $this->call('play-game-arena-bot', ['bot' => false]);
                $this->call('play-game-arena-bot', ['bot' => true]);
            }
            //代码运行区域结束
            $data['message'] = 'Task Executed.';
            $this->logger($data);
            $end = microtime(true);
            $use = $end - $start;
            $use = bcmul($use, 1000000);
            $residue = $this->sleep - $use;
            $this->wait($residue);
            $lock->release();
        } catch (\Exception $ex) {
            $lock->release();
            $data['message'] = $ex->getMessage();
            $this->wait($this->sleep);
        }
    }

    /**
     * 毫秒
     * @param string $time
     */
    protected function wait($time)
    {
        usleep($time);
    }

    protected function logger($message)
    {
        if ($this->debug) {
            $time = (new Carbon)->format('Y-m-d H:i:s.u');
            $this->line($message['message'] . ' - ' . $time);
            \Log::info(null, $message);
        }
    }

    public static function getConfigAll()
    {
        $config = SystemConfig::select(['code', 'value', 'type'])->get()->toArray();
        $configs = [];
        foreach ($config as $item) {
            $configs[$item['code']] = $item['value'];
        }
        return $configs;
    }
}
