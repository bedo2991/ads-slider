<?php

namespace App\Providers;

use App\Models\Realm;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderslistUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public mixed $data;

    private int $realm_id;

    // private boolean $important;

    /**
     * Create a new event instance.
     */
    public function __construct($orderslistData, $realmID, $important = false)
    {
        $this->data = $orderslistData;
        $this->realm_id = $realmID;
        // $this->important = $important;
        $this->data['important'] = $important;
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        if (isset($this->realm_id)) {
            return true;
        }
        Log::critical('Orderslist was not dispatched because it did not have a realm_id', [$this->data]);

        return false;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ol.updated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel(Realm::getBroadcastChannel('orderslist-updates', (int) $this->realm_id)),
        ];
    }
}
