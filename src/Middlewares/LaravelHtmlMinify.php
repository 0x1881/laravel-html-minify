<?php
namespace C4N\LaravelHtmlMinify\Middlewares;

use Closure;
use Illuminate\Http\Response;
use C4N\LaravelHtmlMinify\Facades\LaravelHtmlMinify as LaravelHtmlMinifyFacade;

class LaravelHtmlMinify
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!$this->isMatchUrl($request) && !$this->isMatchRouteName($request)) {
            if ($this->isResponseObject($response) && $this->isHtmlResponse($response) && config('laravel-html-minify.enable')) {
                $response->setContent(LaravelHtmlMinifyFacade::htmlMinify($response->getContent()));
            }
        }

        return $response;
    }

    protected function isResponseObject($response)
    {
        return is_object($response) && $response instanceof Response;
    }

    protected function isHtmlResponse(Response $response)
    {
        return strtolower(strtok($response->headers->get('Content-Type'), ';')) === 'text/html';
    }

    protected function isMatchUrl($request)
    {
        $patterns = config('laravel-html-minify.skip_path', []);

        if (is_countable($patterns) && count($patterns) > 0) {
            foreach ($patterns as $pattern) {
                if ($request->is($pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isMatchRouteName($request)
    {
        $patterns = config('laravel-html-minify.skip_route', []);

        if (is_countable($patterns) && count($patterns) > 0) {
            foreach ($patterns as $pattern) {
                if ($request->routeIs($pattern)) {
                    return true;
                }
            }
        }

        return false;
    }
}
