<?php

namespace App\Filament\Resources\IpRestrictionResource\Pages;

use App\Filament\Resources\IpRestrictionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIpRestriction extends CreateRecord
{
    protected static string $resource = IpRestrictionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        \App\Models\AuditLog::log(
            action: 'ip_restriction_created',
            resource: $this->record,
            notes: "Added {$this->record->type}: {$this->record->ip_address}",
        );
    }
}
