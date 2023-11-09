<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param int $code
     * @param string $message
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected static function apiJson($code = 200,$message = 'ok',$data = [])
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
}
