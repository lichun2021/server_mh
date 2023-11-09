<?php


namespace App\Http\Middleware;

use Closure;

class VerifyUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();
        if ($user !== null && $user->status === 0){
            auth('api')->invalidate();
        }
        return $next($request);
    }
}
