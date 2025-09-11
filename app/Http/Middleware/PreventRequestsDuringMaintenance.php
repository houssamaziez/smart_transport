<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * قائمة المسارات المستثناة من وضع الصيانة.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
