<?php

namespace App\Providers;

use App\Models\Realm;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ItemCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $what;

    public mixed $data;

    /**
     * Create a new event instance.
     */
    public function __construct(string $what, mixed $data)
    {
        $this->what = $what;
        $this->data = $data;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'item.created';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        if (isset($this->data->realm_id)) {
            return true;
        }
        Log::critical('The ItemCreated event was not dispatched because it did not have a realm_id', [$this->data]);

        return false;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel(Realm::getBroadcastChannel('data-updates', (int) $this->data->realm_id)),
        ];
    }
}
