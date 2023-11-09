<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BaiduRecall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baidu-recall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $way = 'GET'.'\n';
        $route = '/api/recall'.'\n';
        $timestamp = time().'\n';
        print_r($timestamp.PHP_EOL);
        $str = md5(uniqid(microtime(true),true)).'\n'.'\n';
        print_r($str.PHP_EOL);
        $secret = 'WTEzqoaaD1HlT86nnoxvFcMR7G3iBbmR'.'\n';
        print_r($way.$route.$timestamp.$str.$secret.PHP_EOL);
        $signature = md5($way.$route.$timestamp.$str.$secret);
        print_r($signature.PHP_EOL);
        return $signature;
    }
}
