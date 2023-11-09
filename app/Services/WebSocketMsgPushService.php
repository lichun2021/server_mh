<?php


namespace App\Services;

use GatewayClient\Gateway;
class WebSocketMsgPushService
{
    /**
     * @var string 网关地址
     */
    private static $registerAddress = '127.0.0.1:1239';

    /**
     * @var bool 持久链接
     */
    private static $persistentConnection = false;

    /**
     * @var string 群组
     */
    public static $group;


    /**
     * 群组消息推送
     * @param string $type 消息类型
     * @param array|object $data 数据
     */
    public static function pushMsg($type, $data)
    {
        self::initGateway();

        $data = [
            'MsgType' => $type,
            'Data' => $data
        ];
        Gateway::sendToGroup(self::$group, json_encode($data));
    }

    /**
     * 加入群
     * @param string $client_id 客户端Id
     * @param string $group 群组
     * @return bool
     */
    public static function joinGroup($client_id, $group)
    {
        self::initGateway();

        if (Gateway::isUidOnline($client_id)) {
            $client_id = Gateway::getClientIdByUid($client_id)[0];
            Gateway::joinGroup($client_id, $group);
            return true;
        }
        return false;
    }

    /**
     * 初始化网关
     */
    private static function initGateway()
    {
        Gateway::$registerAddress = self::$registerAddress;
        Gateway::$persistentConnection = self::$persistentConnection;
    }
}
