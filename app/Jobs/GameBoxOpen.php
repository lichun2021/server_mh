<?php

namespace App\Jobs;

use App\GameArena;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GameBoxOpen implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gameArena;

    /**
     * GameBoxOpen constructor.
     * @param GameArena $gameArena
     */
    public function __construct(GameArena $gameArena)
    {
        $this->gameArena = $gameArena;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('列队执行',$this->gameArena->toArray());
    }
}
