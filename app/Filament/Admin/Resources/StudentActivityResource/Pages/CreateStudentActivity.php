<?php

namespace App\Filament\Admin\Resources\StudentActivityResource\Pages;

use App\Filament\Admin\Resources\StudentActivityResource;
use App\Models\StudentActivity;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateStudentActivity extends CreateRecord
{
    protected static string $resource = StudentActivityResource::class;
    // Adjust afterCreate in CreateStudentActivity page
protected function afterCreate(): void
{
    if (request()->has('student_ids')) {
        $this->record->students()->sync(request('student_ids'));
    }
}


    protected function getRedirectUrl(): string
    {
        // Redirect back to the index page after creation
        return StudentActivityResource::getUrl('index');
    }
}
