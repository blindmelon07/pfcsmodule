<?php

namespace App\Filament\Admin\Resources\GuardianResource\Pages;

use App\Filament\Admin\Resources\GuardianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGuardian extends CreateRecord
{
    protected static string $resource = GuardianResource::class;
    protected function afterSave(): void
    {
        // Sync selected students with the guardian after creation
        $this->record->students()->sync($this->data['student_ids'] ?? []);
    }
}
