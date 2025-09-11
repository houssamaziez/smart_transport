<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * أسماء الكوكيز التي لا يجب تشفيرها.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
