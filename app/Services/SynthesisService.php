<?php

namespace App\Services;

use App\Skins;
/**
 * Class SynthesisService
 * @author 春风 <860646000@qq.com> WeChat：binzhou5
 * Date：2022/2/25
 * Time：21:07
 */
class SynthesisService
{
    private static $percent = [
        'synthesis_a' => [0.5, 0.6],
        'synthesis_b' => [0.6, 0.7],
        'synthesis_c' => [0.7, 0.8],
        'synthesis_d' => [0.8, 0.9],
        'synthesis_e' => [0.9, 1],
        'synthesis_f' => [1, 2],
    ];

    private static $SkinsList = [];

    public static function getSkinId($bean)
    {
        self::initArray($bean);
        $rand = array_rand(self::$SkinsList,1);

        return self::$SkinsList[$rand];
    }

    private static function generateList($start, $end, $quantity)
    {
        $skinsId = Skins::whereBetween('bean', [$start, $end])
            ->pluck('id')
            ->toArray();
        $dataNum = count($skinsId);
        shuffle($skinsId);
        if ($dataNum > $quantity) {
            for ($i = 0; $i < $quantity; $i++) {
                self::$SkinsList[] = $skinsId[$i];
            }
        } else {
            $stopInt = 0;
            for ($i = 0; $i < ceil($quantity / $dataNum); $i++) {
                foreach ($skinsId as $value) {
                    $stopInt++;
                    if ($stopInt > $quantity){
                        continue 2;
                    }
                    self::$SkinsList[] = $value;
                }
            }
        }
    }

    /**
     * @param $bean
     */
    private static function initArray($bean)
    {
        foreach (self::$percent as $key => $value) {
            $configQuantity = getConfig($key);
            if ($configQuantity > 0) {
                self::generateList(bcmul($bean,$value[0],2), bcmul($bean,$value[1],2), $configQuantity);
            }
        }
    }

}
