<?php

namespace App\Filament\Admin\Resources\GuardianResource\Pages;

use App\Filament\Admin\Resources\GuardianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuardians extends ListRecords
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
