<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     */
    protected $middleware = [
        // التعامل مع الثقة بالرؤوس القادمة من البروكسي
        \Illuminate\Http\Middleware\TrustProxies::class,
        // حماية التطبيق من هجمات XSS وCSRF عند الضرورة
        \Illuminate\Http\Middleware\HandleCors::class,
        // التأكد من أن التطبيق يعمل في الصيانة عند الحاجة
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        // التحقق من حجم البيانات القادمة في الطلب
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        // تنظيف البيانات القادمة من الفورم
        \App\Http\Middleware\TrimStrings::class,
        // تحويل الحقول الفارغة إلى null تلقائيًا
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * Middleware groups can be assigned to routes.
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // استخدام CSRF على الويب فقط
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // ✅ السماح بالطلبات القادمة من Frontend موثوقة باستخدام Sanctum
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // تحديد حد للطلبات API
            'throttle:api',
            // ربط المتغيرات في المسارات بالموديلات تلقائيًا
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     */
    protected $routeMiddleware = [
        // المصادقة باستخدام الجلسة أو API
        'auth' => \App\Http\Middleware\Authenticate::class,

        // المصادقة باستخدام Basic Auth
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,

        // تحديد عدد الطلبات
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // التأكد من أن المستخدم مسجل كمستخدم معين
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // ✅ الميدل وير الخاص بالأدوار (مهم جدًا)
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];
}
