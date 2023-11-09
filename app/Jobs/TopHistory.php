<?php

namespace App\Jobs;

use App\Services\TopHistoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TopHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ids;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        TopHistoryService::handle($this->ids);
    }
}
