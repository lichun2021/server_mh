<?php

namespace App\Console\Commands;

use App\BeanRecord;
use App\Services\RechargeService;
use Illuminate\Console\Command;

class AutoRecharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-recharge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '主播点击充值自动到账';

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
        BeanRecord::query()->where('status', 0)->where('created_at', '>=', date('Y-m-d H:i:s', time() - 300))->whereHas('user', function ($query) {
            return $query->where('anchor', 1);
        })->chunk(10, function ($records) {
            \DB::beginTransaction();
            try {
                foreach ($records as $record) {
                    RechargeService::run($record->user_id, $record->bean, false);
                    $record->status = 1;
                    $record->save();
                }
                \DB::commit();
                return 0;
            } catch (\Exception $e) {
                \DB::rollBack();
                $log = [
                    'Message' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine()
                ];
                \Log::error('主播充值自动到账出错',$log);
                return 1;
            }
        });
    }
}
