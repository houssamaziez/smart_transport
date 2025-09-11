<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // ==============================
    // 🟢 CUSTOMER METHODS
    // ==============================

    // إنشاء طلب جديد (للعميل)
    public function createAsCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_address'    => 'required|string|max:255',
            'dropoff_address'   => 'required|string|max:255',
            'price'             => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::create([
            'customer_id'       => $request->user()->id,
            'pickup_address'    => $request->pickup_address,
            'dropoff_address'   => $request->dropoff_address,
            'price'             => $request->price,
            'region'            => $request->user()->region,
            'status'            => 'pending'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Order created successfully!',
            'order'   => $order
        ], 201);
    }

    // جلب طلبات العميل
    public function listForCustomer(Request $request)
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $orders
        ]);
    }

    // تفاصيل طلب
    public function show($id)
    {
        $order = Order::findOrFail($id);

        return response()->json([
            'status' => true,
            'order'  => $order
        ]);
    }

    // إلغاء طلب
    public function cancel($id, Request $request)
    {
        $order = Order::where('id', $id)
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json([
                'status'  => false,
                'message' => 'Order cannot be cancelled at this stage'
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'status'  => true,
            'message' => 'Order cancelled successfully'
        ]);
    }

    // ==============================
    // 🟠 DRIVER METHODS
    // ==============================

    // عرض الطلبات المتاحة في نفس المنطقة
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
            ->whereRaw('LOWER(region) = ?', [strtolower($region)])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $orders
        ]);
    }

    // قبول طلب
    public function acceptOrder($id, Request $request)
    {
        $order = Order::where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        // تحقق من أن السائق في نفس المنطقة
        if (strtolower($order->region) !== strtolower($request->user()->region)) {
            return response()->json([
                'status'  => false,
                'message' => 'You cannot accept orders outside your region'
            ], 403);
        }

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
