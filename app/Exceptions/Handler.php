<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        //if (request()->segment(1) == 'api') {
            switch (true) {
                case $exception instanceof UnauthorizedHttpException:
                    return response()->json([
                        'code' => 401,
                        'message' => '登陆已过期，请重新登陆'
                    ]);

                case $exception instanceof NotFoundHttpException:
                    return response()->json([
                        'code' => 404,
                        'message' => '路由不存在'
                    ]);

                case $exception instanceof \PDOException:
                    //Log::error($exception->getMessage());
                    return response()->json([
                        'code' => 500,
                        'message' => 'Sql Error'.$exception->getMessage()
                    ]);
                case $exception instanceof MethodNotAllowedHttpException:
                    return response()->json([
                        'code' => 405,
                        'message' => 'Method not allowed'
                    ]);
            }
        //}

        return parent::render($request, $exception);
    }
}
