<?php

namespace App\Filament\Resources\IpRestrictionResource\Pages;

use App\Filament\Resources\IpRestrictionResource;
use Filament\Resources\Pages\EditRecord;

class EditIpRestriction extends EditRecord
{
    protected static string $resource = IpRestrictionResource::class;

    protected function afterSave(): void
    {
        \App\Models\AuditLog::log(
            action: 'ip_restriction_updated',
            resource: $this->record,
            notes: "Updated {$this->record->type}: {$this->record->ip_address}",
        );
    }
}
