<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    /**
     * ✅ إضافة تقييم للطلب
     */
    public function store($id, Request $request)
    {
        $order = Order::where('id', $id)
            ->where('customer_id', $request->user()->id) // تأكد أن الطلب يخص العميل
            ->where('status', 'completed')               // التقييم فقط بعد اكتمال الطلب
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found or not completed'
            ], 404);
        }

        // التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // منع تكرار التقييم لنفس الطلب
        if (Rating::where('order_id', $order->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'This order has already been rated'
            ], 400);
        }

        $rating = Rating::create([
            'order_id'    => $order->id,
            'driver_id'   => $order->driver_id,
            'customer_id' => $request->user()->id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Rating submitted successfully',
            'rating'  => $rating
        ]);
    }
}
