<?php

namespace App\Services;

use Pusher\Pusher;
use Illuminate\Support\Facades\Log;

class PusherService
{
    protected $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            config('broadcasting.connections.pusher.options')
        );
    }

    /**
     * Broadcast order created event to drivers in the same region
     */
    public function broadcastOrderCreated($order)
    {
        try {
            $this->pusher->trigger(
                'orders.' . $order->region,
                'order.created',
                [
                    'order' => [
                        'id' => $order->id,
                        'type' => $order->type,
                        'pickup_location' => $order->pickup_location,
                        'dropoff_location' => $order->dropoff_location,
                        'price' => $order->price,
                        'package_type' => $order->package_type,
                        'passenger_count' => $order->passenger_count,
                        'status' => $order->status,
                        'region' => $order->region,
                        'created_at' => $order->created_at,
                        'scheduled_at' => $order->scheduled_at,
                    ],
                    'message' => 'New order available in your region!',
                    'timestamp' => now()->toISOString(),
                ]
            );

            Log::info('Order created broadcast sent', ['order_id' => $order->id, 'region' => $order->region]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast order created', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Broadcast order status update to customer and driver
     */
    public function broadcastOrderStatusUpdate($order, $oldStatus, $newStatus)
    {
        try {
            $statusMessages = [
                'pending' => 'Order is pending',
                'accepted' => 'Order has been accepted by driver',
                'on_the_way' => 'Driver is on the way to pickup location',
                'picked_up' => 'Driver has picked up the order',
                'in_progress' => 'Order is being delivered',
                'completed' => 'Order has been completed',
                'cancelled' => 'Order has been cancelled',
            ];

            $data = [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => $statusMessages[$newStatus] ?? 'Order status updated',
                'driver_name' => $order->driver ? $order->driver->name : null,
                'timestamp' => now()->toISOString(),
            ];

            // Broadcast to customer
            $this->pusher->trigger(
                'customer.' . $order->customer_id,
                'order.status.updated',
                $data
            );

            // Broadcast to driver if assigned
            if ($order->driver_id) {
                $this->pusher->trigger(
                    'driver.' . $order->driver_id,
                    'order.status.updated',
                    $data
                );
            }

            Log::info('Order status update broadcast sent', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast order status update', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Broadcast driver status update
     */
    public function broadcastDriverStatusUpdate($driver, $oldStatus, $newStatus)
    {
        try {
            $statusMessages = [
                'available' => 'Driver is now available',
                'busy' => 'Driver is currently busy',
                'offline' => 'Driver is offline',
            ];

            $data = [
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'region' => $driver->region,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => $statusMessages[$newStatus] ?? 'Driver status updated',
                'timestamp' => now()->toISOString(),
            ];

            // Broadcast to drivers in the same region
            $this->pusher->trigger(
                'drivers.' . $driver->region,
                'driver.status.updated',
                $data
            );

            // Broadcast to admin
            $this->pusher->trigger(
                'admin.drivers',
                'driver.status.updated',
                $data
            );

            Log::info('Driver status update broadcast sent', [
                'driver_id' => $driver->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast driver status update', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send notification to specific user
     */
    public function sendNotificationToUser($userId, $event, $data)
    {
        try {
            $this->pusher->trigger(
                'user.' . $userId,
                $event,
                $data
            );

            Log::info('Notification sent to user', ['user_id' => $userId, 'event' => $event]);
        } catch (\Exception $e) {
            Log::error('Failed to send notification to user', ['error' => $e->getMessage()]);
        }
    }
}
