<?php

namespace App\Console\Commands;

use App\Room;
use Illuminate\Console\Command;

class RoomTimed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'room-timed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Roll时间自动上架';

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
        Room::query()->where('status', -1)->where('start_at', '<=', now())->chunk(10, function ($rooms) {
            foreach ($rooms as $room){
                $room->status = 0;
                $room->save();
            }
        });
        return 1;
    }
}
