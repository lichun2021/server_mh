<?php


namespace App\Admin\Controllers;

use App\User;
use App\Skins;
use App\RoomAward;
use Encore\Admin\Controllers\AdminController;

class ApiController extends AdminController
{
    /**
     * 饰品搜索下拉 用于宝箱饰品添加
     * @return mixed
     */
    public function skins()
    {
        $q = request()->get('q');
        $skins = Skins::select(['id','name as text','dura'])->where('name','like',"%$q%")
            ->paginate()->toArray();
        foreach ($skins['data'] as $key => $item){
            if ($item['dura'] != 0){
                $skins['data'][$key]['text'] = $skins['data'][$key]['text'].' ('. $skins['data'][$key]['dura_alias'] .')';
            }
            unset($skins['data'][$key]['dura'], $skins['data'][$key]['dura_alias']);
        }
        return $skins;
    }

    /**
     * 饰品搜索下拉 用于夺宝饰品下拉
     * @return mixed
     */
    public function snatchSkins()
    {
        $q = request()->get('q');
        $skins = Skins::select(['id','name as text','dura','bean'])->where('name','like',"%$q%")
            ->paginate()->toArray();
        foreach ($skins['data'] as $key => $item){
            if ($item['dura'] != 0){
                $skins['data'][$key]['text'] = $skins['data'][$key]['text'].' ('. $skins['data'][$key]['dura_alias'] .')'.'&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;$'.$item['bean'];
            } else {
                $skins['data'][$key]['text'] = $skins['data'][$key]['text'].'&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;$'.$item['bean'];
            }
            unset($skins['data'][$key]['dura'], $skins['data'][$key]['dura_alias'], $skins['data'][$key]['bean']);
        }
        return $skins;
    }

    public function users()
    {
        $q = request()->get('q');
        $user = User::query()->select(['id','name as text'])->where('name','like',"%$q%")
            ->paginate()->toArray();
        return $user;
    }
    
    public function roomAwards()
    {
        $room_id = request()->get('room_id');
        $resp = RoomAward::select(['room_awards.id','room_awards.box_record_id','room_awards.designated_user','box_records.name','box_records.bean','box_records.dura'])
            ->leftJoin('box_records','box_records.id','=','room_awards.box_record_id')
            ->where(['room_awards.room_id' => $room_id, 'room_awards.designated_user' => 0])
            ->orderByDesc('box_records.bean')
            ->get()
            ->toArray();
        
        $data = [];
        foreach ($resp as $item) {
            $award = [
                'id' => $item['id'],
                'text' => $item['name'],
            ];
            if ($item['dura'] > 0) {
                $award['text'] .= ' (' . Skins::$fields['dura'][$item['dura']] . ')'.'<div class="pull-right">'.$item['bean'].'</div>';
            } else {
                $award['text'] .= '<div class="pull-right">'.$item['bean'].'</div>';
            }
            $data[] = $award;
        }

        return $data;
    }
}
