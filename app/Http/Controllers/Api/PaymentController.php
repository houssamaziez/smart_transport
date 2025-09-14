<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function record(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'method'     => 'required|in:cash,wallet',
            'amount'     => 'required|numeric|min:1'
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->status !== 'delivered') {
            return response()->json([
                'status'  => false,
                'message' => 'Order must be delivered before payment'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // إنشاء سجل الدفع
            $payment = Payment::create([
                'order_id'  => $order->id,
                'customer_id' => $order->customer_id,
                'driver_id'   => $order->driver_id,
                'amount'      => $request->amount,
                'method'      => $request->method,
                'status'      => 'paid'
            ]);

            // تحديث حالة الطلب إلى مكتمل
            $order->update(['status' => 'completed']);

            // إذا الدفع بالمحفظة → خصم من العميل و إضافة للسائق
            if ($request->method === 'wallet') {
                $order->customer->decrement('wallet_balance', $request->amount);
                $order->driver->increment('wallet_balance', $request->amount);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Payment recorded successfully',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Payment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
