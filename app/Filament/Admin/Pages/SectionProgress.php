<?php

namespace App\Filament\Admin\Pages;

use App\Models\Section;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SectionProgress extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Modules';

    protected static string $view = 'filament.admin.pages.section-progress';

    public function getSectionData()
    {
        $user = Auth::user();

        // Initialize the sections query with eager loading of students and grades
        $sectionsQuery = Section::with('students.grades');

        // If the user is a teacher, retrieve only sections assigned to them through the teacher relationship
        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher; // Assuming User model has a `teacher` relationship
            if ($teacher) {
                $sectionsQuery->whereHas('teachers', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                });
            }
        }

        $sections = $sectionsQuery->get();

        $chartData = [
            'labels' => [],
            'averages' => [],
        ];

        foreach ($sections as $section) {
            // Calculate the average grade for each student in the section
            foreach ($section->students as $student) {
                if ($student->grades->isNotEmpty()) {
                    $averageGrade = $student->grades->map(function ($grade) {
                        return ($grade->first_quarter + $grade->second_quarter + $grade->third_quarter + $grade->fourth_quarter) / 4;
                    })->avg();

                    // Add the label and average for charting
                    $chartData['labels'][] = "{$section->name} - {$student->user->name}";
                    $chartData['averages'][] = round($averageGrade, 2);
                }
            }
        }

        return $chartData;
    }
}
