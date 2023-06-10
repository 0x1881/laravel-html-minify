<?php

if (!function_exists('is_countable')) {
    function is_countable($c)
    {
        return is_array($c) || $c instanceof Countable;
    }
}
