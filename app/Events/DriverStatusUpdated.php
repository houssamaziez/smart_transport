<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driver;
    public $oldStatus;
    public $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(User $driver, string $oldStatus, string $newStatus)
    {
        $this->driver = $driver;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('drivers.' . $this->driver->region),
            new PrivateChannel('admin.drivers'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'driver.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $statusMessages = [
            'available' => 'Driver is now available',
            'busy' => 'Driver is currently busy',
            'offline' => 'Driver is offline',
        ];

        return [
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->name,
            'region' => $this->driver->region,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => $statusMessages[$this->newStatus] ?? 'Driver status updated',
            'timestamp' => now()->toISOString(),
        ];
    }
}
