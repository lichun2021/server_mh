<?php

namespace App\Jobs;

use App\User;
use App\BoxRecord;
use App\Services\ZbtService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBuy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $box_record;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BoxRecord $box_record)
    {
        $this->box_record = $box_record;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->box_record->status == 0) {
            $user = User::find($this->box_record->user_id);

            $result = ZbtService::buy(
                $this->box_record->code,
                $this->box_record->item_name,
                $user->steam_url
            );

            if ($result['code'] == 1) {
                $this->box_record->status = 1;
            } else {
                $this->box_record->status = 2;
                $this->box_record->back_message = $result['message'];
            }

            $this->box_record->save();
        }
    }
}
