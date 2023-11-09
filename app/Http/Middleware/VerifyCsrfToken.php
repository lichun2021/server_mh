<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'zbt/notify',
        'bus/notify',
        'callback/alipay',
        'callback/jiuja_pay',
        'callback/fxkpay',
        'callback/umi_strong_pay',
        'callback/vggstorepay',
        'callback/hnsqpay',
        'youpin/notify',
    ];

    public function handle($request, Closure $next)
    {
        $this->except[] = config('admin.route.prefix').'/file/image';
        return parent::handle($request, $next);
    }
}
