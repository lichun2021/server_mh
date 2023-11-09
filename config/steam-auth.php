<?php

return [

    /*
     * Redirect URL after login
     */
    'redirect_url' => 'https://www.chunblog.com',

    /*
     * Realm override. Bypass domain ban by Valve.
     * Use alternative domain with redirection to main for authentication (banned by valve).
     */
    'realm' => 'www.chunblog.com',

    /*
     * API Key (set in .env file) [http://steamcommunity.com/dev/apikey]
     */
    'api_key' => env('STEAM_API_KEY', '4E450DD8549334E3FF1BA370AADA2C3C'),

    /*
     * Is using https ?
     */
    'https' => true,

    /**
     * Proxy gateway
     */
    'proxy_gateway' => 'http://steam.proxy.chunblog.com/steamProxy.php',

    /**
     * Proxy SteamInfoUrlGateway
     */
    'steam_info_gateway' => 'http://steam.proxy.chunblog.com/steamProxy.php?key=%s&steamids=%s'
];
