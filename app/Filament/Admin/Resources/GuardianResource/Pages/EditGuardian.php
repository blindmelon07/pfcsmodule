<?php

namespace App\Filament\Admin\Resources\GuardianResource\Pages;

use App\Filament\Admin\Resources\GuardianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuardian extends EditRecord
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        // Sync selected students with the guardian after updating
        $this->record->students()->sync($this->data['student_ids'] ?? []);
    }
}
