<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SubMailService;

class SendSmsNoticeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mobile;

    /**
     * SendSmsNoticeJob constructor.
     * @param string $mobile
     */
    public function __construct($mobile)
    {
        $this->mobile = $mobile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        SubMailService::sendNotice($this->mobile);
    }
}
