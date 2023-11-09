<?php

namespace App\Console\Commands;

use App\Box;
use Illuminate\Console\Command;

class BoxCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'box-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查宝箱收益';

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
        Box::query()->chunk(10,function ($boxs){
            foreach ($boxs as $box){
                $contains = \App\BoxContain::with(['skins:id,bean,dura'])
                    ->select(['skin_id', 'real_odds', 'game_odds'])
                    ->where('box_id', $box->id)
                    ->get()
                    ->toArray();
                $prizePool = 0;
                $prizePool2 = 0;
                $real_odds = 0;
                $game_odds = 0;
                foreach ($contains as $contain) {
                    if ($contain['real_odds'] > 0) {
                        $prizePool += $contain['real_odds'] * $contain['skins']['bean'];
                        $real_odds += $contain['real_odds'];
                        
                    }
                    if ($contain['game_odds'] > 0) {
                        $prizePool2 += $contain['game_odds'] * $contain['skins']['bean'];
                        $game_odds += $contain['game_odds'];
                    }
                }
                $costPerRound = bcmul($box->bean, $real_odds, 2);
                $gameCostPerRound = bcmul($box->game_bean, $game_odds, 2);
                $profit = $costPerRound - $prizePool;
                $gameProfit = $gameCostPerRound - $prizePool2;

                $profitMargin = $profit <= 0 ? 0 : bcmul($profit / $costPerRound, 100, 2);
                $gameProfitMargin = $gameProfit <= 0 ? 0 : bcmul($gameProfit / $gameCostPerRound, 100, 2);
                if ($profitMargin < getConfig('box_profit_margin')){
                    $box->is_putaway = 0;
                    $box->save();
                }
                if ($gameProfitMargin < getConfig('box_profit_margin')){
                    $box->is_game = 0;
                    $box->save();
                }
            }
        });
        return 0;
    }
}
