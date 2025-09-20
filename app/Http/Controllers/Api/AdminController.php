<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;

class AdminController extends Controller
{
    /**
     * ✅ عرض جميع المستخدمين
     */
    public function listUsers()
    {
        $users = User::all();

        return response()->json([
            'status' => true,
            'users' => $users
        ]);
    }

    /**
     * ✅ حظر مستخدم (Ban)
     */
    public function banUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->is_active = false; // حظر
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User banned successfully',
            'user' => $user
        ]);
    }

    /**
     * ✅ عرض جميع الطلبات
     */
    public function listOrders()
    {
        $orders = Order::with(['customer', 'driver'])->get();

        return response()->json([
            'status' => true,
            'orders' => $orders
        ]);
    }

    /**
     * ✅ عرض جميع المدفوعات
     */
    public function listPayments()
    {
        $payments = Payment::with(['user', 'order'])->get();

        return response()->json([
            'status' => true,
            'payments' => $payments
        ]);
    }

    /**
     * ✅ تقارير وإحصائيات النظام
     */
    public function reports()
    {
        $totalUsers   = User::count();
        $activeUsers  = User::where('is_active', true)->count();
        $totalOrders  = Order::count();
        $completed    = Order::where('status', 'completed')->count();
        $cancelled    = Order::where('status', 'cancelled')->count();
        $totalPayments = Payment::sum('amount');

        return response()->json([
            'status' => true,
            'reports' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                ],
                'orders' => [
                    'total' => $totalOrders,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                ],
                'payments' => [
                    'total_amount' => $totalPayments,
                ]
            ]
        ]);
    }
}
