<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // ✅ إذا لم يكن المستخدم مسجلاً دخوله
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        // ✅ إذا كان الدور غير مصرح به
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'status' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المورد'
            ], 403);
        }

        return $next($request);
    }
}
