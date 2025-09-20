<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DriverEarning;
use App\Events\DriverStatusUpdated;
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


    // ✅ عرض سجل الأرباح
    public function earnings(Request $request)
    {
        $driver = $request->user();

        $earnings = DriverEarning::where('driver_id', $driver->id)
            ->with('order')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'total_earnings' => $earnings->sum('amount'),
            'records' => $earnings
        ]);
    }

    // ✅ جلب الطلبات القريبة من موقع السائق
    public function nearbyRequests(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'nullable|numeric|min:0.1|max:50' // radius in kilometers
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $driver = $request->user();
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 10; // default 10km radius

        // جلب الطلبات المتاحة في نفس المنطقة أولاً
        $orders = Order::where('status', 'pending')
            ->where('region', $driver->region)
            ->get();

        // فلترة الطلبات حسب المسافة (محاكاة - يمكن تحسينها باستخدام قاعدة بيانات جغرافية)
        $nearbyOrders = $orders->filter(function ($order) use ($latitude, $longitude, $radius) {
            // محاكاة حساب المسافة - في التطبيق الحقيقي استخدم Haversine formula
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $this->extractLatFromLocation($order->pickup_location),
                $this->extractLngFromLocation($order->pickup_location)
            );

            return $distance <= $radius;
        });

        return response()->json([
            'status' => true,
            'message' => 'Nearby requests retrieved successfully',
            'radius_km' => $radius,
            'driver_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ],
            'orders' => $nearbyOrders->values()
        ]);
    }

    // ✅ جلب طلبات الطرود المتاحة فقط
    public function availableParcels(Request $request)
    {
        $driver = $request->user();
        $region = $driver->region;

        if (!$region) {
            return response()->json([
                'status'  => false,
                'message' => 'You must set your region in your profile to get parcel orders'
            ], 400);
        }

        // جلب طلبات الطرود المتاحة في نفس المنطقة
        $parcelOrders = Order::where('status', 'pending')
            ->where('type', 'parcel')
            ->where('region', $region)
            ->orderBy('created_at', 'desc')
            ->get();

        // إضافة معلومات إضافية للطرود
        $parcelsWithDetails = $parcelOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'type' => $order->type,
                'package_type' => $order->package_type,
                'pickup_location' => $order->pickup_location,
                'dropoff_location' => $order->dropoff_location,
                'price' => $order->price,
                'status' => $order->status,
                'region' => $order->region,
                'created_at' => $order->created_at,
                'scheduled_at' => $order->scheduled_at,
                'estimated_delivery_time' => $this->estimateDeliveryTime($order->package_type),
                'delivery_priority' => $this->getDeliveryPriority($order->package_type)
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Available parcel orders retrieved successfully',
            'total_parcels' => $parcelsWithDetails->count(),
            'parcels' => $parcelsWithDetails
        ]);
    }

    // ✅ حساب المسافة بين نقطتين (Haversine formula)
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    // ✅ استخراج خط العرض من موقع الاستلام (محاكاة)
    private function extractLatFromLocation($location)
    {
        // في التطبيق الحقيقي، يجب أن يكون لديك جدول منفصل للمواقع مع الإحداثيات
        // هنا نعيد قيمة محاكاة
        return 24.7136 + (rand(-100, 100) / 1000); // الرياض تقريباً
    }

    // ✅ استخراج خط الطول من موقع الاستلام (محاكاة)
    private function extractLngFromLocation($location)
    {
        // في التطبيق الحقيقي، يجب أن يكون لديك جدول منفصل للمواقع مع الإحداثيات
        // هنا نعيد قيمة محاكاة
        return 46.6753 + (rand(-100, 100) / 1000); // الرياض تقريباً
    }

    // ✅ تقدير وقت التسليم حسب نوع الطرد
    private function estimateDeliveryTime($packageType)
    {
        $deliveryTimes = [
            'document' => '15-30 minutes',
            'small_package' => '20-40 minutes',
            'medium_package' => '30-60 minutes',
            'large_package' => '45-90 minutes',
            'fragile' => '30-50 minutes',
            'food' => '10-25 minutes',
            'electronics' => '25-45 minutes'
        ];

        return $deliveryTimes[$packageType] ?? '30-60 minutes';
    }

    // ✅ تحديد أولوية التسليم حسب نوع الطرد
    private function getDeliveryPriority($packageType)
    {
        $priorities = [
            'food' => 'high',
            'document' => 'high',
            'electronics' => 'medium',
            'fragile' => 'medium',
            'small_package' => 'medium',
            'medium_package' => 'low',
            'large_package' => 'low'
        ];

        return $priorities[$packageType] ?? 'medium';
    }

    // ✅ تحديث حالة السائق (متصل/غير متصل/مشغول)
    public function updateDriverStatus(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => 'required|in:available,busy,offline'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $driver = $request->user();
        $newStatus = $request->status;
        $oldStatus = $driver->status ?? 'offline';

        // تحديث حالة السائق
        $driver->update(['status' => $newStatus]);

        // ✅ Broadcast driver status update
        broadcast(new DriverStatusUpdated($driver, $oldStatus, $newStatus));

        // إضافة معلومات إضافية للاستجابة
        $statusMessages = [
            'available' => 'Driver is now available for new orders',
            'busy' => 'Driver is currently busy with an order',
            'offline' => 'Driver is offline and not accepting orders'
        ];

        return response()->json([
            'status' => true,
            'message' => $statusMessages[$newStatus],
            'driver_status' => [
                'previous_status' => $oldStatus,
                'current_status' => $newStatus,
                'updated_at' => now()->toISOString()
            ],
            'driver_info' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'region' => $driver->region,
                'status' => $newStatus
            ]
        ]);
    }

    // ✅ جلب حالة السائق الحالية
    public function getDriverStatus(Request $request)
    {
        $driver = $request->user();

        return response()->json([
            'status' => true,
            'driver_status' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'region' => $driver->region,
                'current_status' => $driver->status ?? 'offline',
                'last_updated' => $driver->updated_at
            ]
        ]);
    }
}
