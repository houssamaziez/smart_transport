<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * قائمة المسارات المستثناة من التحقق من CSRF.
     *
     * @var array<int, string>
     */
    protected $except = [
        // ضع مسارات API إذا أردت تجاوز CSRF
    ];
}
