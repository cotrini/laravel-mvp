<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandlePostTooLarge
{
    public function __construct(
        private readonly ValidatePostSize $validatePostSize,
        private readonly ExceptionHandler $exceptionHandler,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $this->validatePostSize->handle($request, $next);
        } catch (PostTooLargeException $exception) {
            return $this->exceptionHandler->render($request, $exception);
        }
    }
}
