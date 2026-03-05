<?php

namespace App\Filament\Resources\IpRestrictionResource\Pages;

use App\Filament\Resources\IpRestrictionResource;
use Filament\Resources\Pages\ListRecords;

class ListIpRestrictions extends ListRecords
{
    protected static string $resource = IpRestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
