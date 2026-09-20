<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityAuditEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new security audit event instance.
     */
    public function __construct(
        public string $action,
        public string $description,
        public ?int $userId = null,
        public ?int $realmId = null,
        public ?string $ip = null,
        public array $context = []
    ) {
        $this->userId = $this->userId ?? auth()->id();
        $this->realmId = $this->realmId ?? auth()->user()?->realm_id;
        $this->ip = $this->ip ?? request()?->ip();
    }
}
