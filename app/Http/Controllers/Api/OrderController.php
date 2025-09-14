<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DriverEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // ==============================
    // 🟢 CUSTOMER METHODS
    // ==============================

    // إنشاء طلب جديد
    public function createAsCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'             => 'required|in:parcel,ride',
            'pickup_location'  => 'required|string|max:255',
            'dropoff_location' => 'required|string|max:255',
            'price'            => 'required|numeric|min:1',
            // parcel only
            'package_type'     => 'required_if:type,parcel',
            // ride only
            'passenger_count'  => 'required_if:type,ride|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::create([
            'type'             => $request->type,
            'pickup_location'  => $request->pickup_location,
            'dropoff_location' => $request->dropoff_location,
            'price'            => $request->price,
            'package_type'     => $request->package_type,
            'passenger_count'  => $request->passenger_count,
            'status'           => 'pending',
            'region'           => $request->user()->region,
            'customer_id'      => $request->user()->id,
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

    // ✅ تحديث حالة الطلب (driver actions)
    public function updateStatus($id, Request $request)
    {
        $order = Order::where('id', $id)
            ->where('driver_id', $request->user()->id) // تأكد أن السائق هو نفسه
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:on_the_way,picked_up,in_progress,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $newStatus = $request->status;

        $order->update([
            'status' => $newStatus
        ]);

        // ✅ عند اكتمال الطلب نسجل ربح للسائق
        if ($newStatus === 'completed') {
            DriverEarning::create([
                'driver_id' => $request->user()->id,
                'order_id'  => $order->id,
                'amount'    => $order->price, // ممكن تعدلها لنسبة لاحقاً
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => "Order status updated to {$newStatus}",
            'order'   => $order
        ]);
    }
    // 🟢 إنشاء رحلة مجدولة
public function createScheduled(Request $request)
{
    $validator = Validator::make($request->all(), [
        'type'             => 'required|in:parcel,ride',
        'pickup_location'  => 'required|string|max:255',
        'dropoff_location' => 'required|string|max:255',
        'price'            => 'required|numeric|min:1',
        'scheduled_at'     => 'required|date|after:now', // ✅ ضروري تاريخ مستقبلي
        // parcel only
        'package_type'     => 'required_if:type,parcel',
        // ride only
        'passenger_count'  => 'required_if:type,ride|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $order = Order::create([
        'type'             => $request->type,
        'pickup_location'  => $request->pickup_location,
        'dropoff_location' => $request->dropoff_location,
        'price'            => $request->price,
        'package_type'     => $request->package_type,
        'passenger_count'  => $request->passenger_count,
        'status'           => 'pending',
        'scheduled_at'     => $request->scheduled_at, // ✅ وقت الجدولة
        'region'           => $request->user()->region,
        'customer_id'      => $request->user()->id,
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Scheduled order created successfully!',
        'order'   => $order
    ], 201);
}

}
