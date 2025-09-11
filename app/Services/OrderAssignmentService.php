<?php
namespace App\Services;

use App\Models\Order;
use App\Models\User;

class OrderAssignmentService
{
    public function notifyDrivers(Order $order)
    {
        // منطق: إيجاد سائقين قريبين عبر مواقعهم (latitude/longitude)
        // ثم إرسال إشعار FCM أو Websocket event
    }

    public function assignDriver(Order $order, User $driver)
    {
        $order->driver_id = $driver->id;
        $order->status = 'accepted';
        $order->save();
        // send notifications...
    }
}
