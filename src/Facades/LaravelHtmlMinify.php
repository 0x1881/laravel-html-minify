<?php

namespace C4N\LaravelHtmlMinify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \C4N\LaravelHtmlMinify\Skeleton\SkeletonClass
 */
class LaravelHtmlMinify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-html-minify';
    }

    public static function htmlMinify(string|null $html = null)
    {
        return (new \C4N\LaravelHtmlMinify\LaravelHtmlMinify)->htmlMinify($html);
    }
}
