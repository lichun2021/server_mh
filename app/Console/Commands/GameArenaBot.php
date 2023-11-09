<?php


namespace App\Console\Commands;

use App\Box;
use App\GameRanking;
use App\Services\GameArenaService;
use App\GameArena;
use App\GameArenaBox;
use App\GameArenaUser;
use App\Services\BoxService;
use App\Services\WebSocketMsgPushService;
use Illuminate\Console\Command;

/**
 * Class GameArenaBot
 * @package App\Console\Commands
 * @author 春风 <860646000@qq.com>
 */
class GameArenaBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game-arena-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '对战机器人';

    /**
     * @var string 对战频道
     */
    private static $group_id = 'csgo_atm_game_arena';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        WebSocketMsgPushService::$group = self::$group_id;
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        $gameArenaNum = GameArena::where(['status' => 0])->count('id');
        if ($gameArenaNum < getConfig('game_arena_bot_num')) {
            //创建对战房间
            \DB::beginTransaction();
            try {
                $user_id = GameArenaService::getRandomUserId();
                $bot = GameArenaService::getRandomBot();

                //创建对战
                $gameArena = new GameArena();
                $gameArena->create_user_id = $user_id;
                $gameArena->bot_id = $bot['botId'];
                $gameArena->user_num = $bot['userNum'];
                $gameArena->box_num = count($bot['boxIds']);
                $gameArena->save();

                $total_bean = 0;
                if (empty($bot['boxIds'])){
                    throw new \Exception('对战机器人房间内未配置宝箱');
                }
                foreach ($bot['boxIds'] as $box_id) {
                    $box = Box::where('id', $box_id)
                        ->where(['is_game' => 1])
                        ->first();

                    $total_bean += $box->game_bean;
                    $gameArenaBox = new GameArenaBox();
                    $gameArenaBox->game_arena_id = $gameArena->id;
                    $gameArenaBox->box_id = $box->id;
                    $gameArenaBox->box_bean = $box->game_bean;
                    $gameArenaBox->save();
                }

                //对战总价值写入;
                $gameArena->total_bean = $total_bean;
                $gameArena->save();
                //写入用户列表
                $gameArenaUser = new GameArenaUser();
                $gameArenaUser->game_arena_id = $gameArena->id;
                $gameArenaUser->user_id = $user_id;
                $gameArenaUser->seat = 0;
                $gameArenaUser->save();
                //排行统计
                GameRanking::write($user_id, $total_bean);
                //查询对战
                $game_arena_id = $gameArena->id;
                $data = GameArena::with([
                    'game_arena_box' => function ($query) {
                        return $query->with(['box:id,name,game_bean,intact_cover'])
                            ->select(['id', 'game_arena_id', 'box_id']);
                    },
                    'game_arena_player' => function ($query) {
                        return $query->with(['user:id,name,avatar'])
                            ->select(['id', 'game_arena_id', 'user_id', 'seat']);
                    }])
                    ->where('id', $game_arena_id)
                    ->first()->toArray();
                //预开箱
                BoxService::open($gameArena->id, $user_id, $gameArenaUser);
                //全频道推送
                WebSocketMsgPushService::pushMsg('CreateGroup', $data);
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollBack();
                echo '对战机器人机器人错误：' . $e->getMessage();
                \Log::error('对战机器人机器人错误：' . $e->getMessage().' '.$e->getFile().' '.$e->getLine());
            }
        }
    }
}
