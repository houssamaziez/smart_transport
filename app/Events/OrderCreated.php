<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders.' . $this->order->region),
            new PrivateChannel('drivers.' . $this->order->region),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'type' => $this->order->type,
                'pickup_location' => $this->order->pickup_location,
                'dropoff_location' => $this->order->dropoff_location,
                'price' => $this->order->price,
                'package_type' => $this->order->package_type,
                'passenger_count' => $this->order->passenger_count,
                'status' => $this->order->status,
                'region' => $this->order->region,
                'created_at' => $this->order->created_at,
                'scheduled_at' => $this->order->scheduled_at,
            ],
            'message' => 'New order available in your region!',
            'timestamp' => now()->toISOString(),
        ];
    }
}
