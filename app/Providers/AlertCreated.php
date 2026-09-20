<?php

namespace App\Providers;

use App\Models\Realm;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public mixed $data;

    /**
     * Create a new event instance.
     */
    public function __construct($alertData)
    {
        $this->data = $alertData;
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        if (isset($this->data->realm_id)) {
            return true;
        }
        Log::critical('Alert was not dispatched because it did not have a realm_id', [$this->data]);

        return false;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel(Realm::getBroadcastChannel('alerts', (int) $this->data->realm_id)),
        ];
    }
}
