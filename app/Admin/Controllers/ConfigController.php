<?php
/**
 * Author：春风
 * WeChat：binzhou5
 * Date：2020/10/20 14:31
 */

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets;
use App\Admin\Forms\Settings;
class ConfigController extends AdminController
{
    public function setting(Content $content)
    {
        return $content
            ->title('网站设置')
            ->body(Widgets\Tab::forms([
                'basic'    => Settings\SystemConfig::class,
                'synthesis'    => Settings\SynthesisConfig::class,
                'robot'    => Settings\RobotConfig::class,
                //'newUserBox'    => Settings\NewUserBoxConfig::class,
            ]));
    }
}
