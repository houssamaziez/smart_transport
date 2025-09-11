<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    // ✅ جلب الطلبات المتاحة في نفس منطقة السائق
    public function availableOrders(Request $request)
    {
        $region = $request->user()->region;

        if (!$region) {
            return response()->json([
                'status'  => false,
                'message' => 'You must set your region in your profile to get orders'
            ], 400);
        }

        $orders = Order::where('status', 'pending')
            ->where('region', $region)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $orders
        ]);
    }

    // ✅ قبول الطلب
    public function acceptOrder(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => 'Order not available'
            ], 404);
        }

        // تعيين السائق للطلب
        $order->update([
            'driver_id' => $request->user()->id,
            'status'    => 'accepted'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Order accepted successfully',
            'order'   => $order
        ]);
    }
}
