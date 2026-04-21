<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $userId = session('user_id');

        Audit::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => Request::ip(),
        ]);
    }
}
