<?php

namespace App\Listeners;

use App\Events\SecurityAuditEvent;
use Illuminate\Support\Facades\Log;

class LogSecurityAudit
{
    /**
     * Handle the event.
     */
    public function handle(SecurityAuditEvent $event): void
    {
        Log::channel('security')->info($event->description, [
            'action' => $event->action,
            'user_id' => $event->userId,
            'realm_id' => $event->realmId,
            'ip' => $event->ip,
            'context' => $event->context,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
