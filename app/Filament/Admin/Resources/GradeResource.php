<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GradeResource\Pages;
use App\Filament\Admin\Resources\GradeResource\RelationManagers;
use App\Models\Grade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
    
        // Check if the user is authenticated
        if (auth()->check()) {
            $user = auth()->user();
    
            // If the user has the "super_admin" role, allow access to all records
            if ($user->hasRole('super_admin')) {
                return $query;
            }
    
            // If the user has the "teacher" role, filter to show only students in their assigned sections
            if ($user->hasRole('teacher')) {
                $teacher = $user->teacher; // Assuming User model has a 'teacher' relationship
                if ($teacher) {
                    return $query->whereHas('student', function (Builder $studentQuery) use ($teacher) {
                        $studentQuery->whereIn('section_id', $teacher->sections->pluck('id'));
                    });
                }
                return $query->whereRaw('0 = 1'); // Return no records if no teacher relationship
            }
    
            // If the user has the "guardian" role, filter to show only related students' records
            if ($user->hasRole('guardian')) {
                $guardian = $user->guardian; // Assuming User model has a 'guardian' relationship
                if ($guardian) {
                    return $query->whereHas('student', function (Builder $studentQuery) use ($guardian) {
                        $studentQuery->whereIn('id', $guardian->students->pluck('id'));
                    });
                }
                return $query->whereRaw('0 = 1'); // Return no records if no guardian relationship
            }
    
            // If the user has the "student" role, filter to show only their own grades
            if ($user->hasRole('student')) {
                $student = $user->student; // Assuming User model has a 'student' relationship
                if ($student) {
                    // Show only grades for the logged-in student
                    return $query->where('student_id', $student->id);
                }
                return $query->whereRaw('0 = 1'); // Return no records if no student relationship
            }
    
            // Restrict access for other roles (optional)
            return $query->whereRaw('0 = 1');
        }
    
        // Fallback for unauthenticated users (shouldn't happen in this context)
        return $query->whereRaw('0 = 1'); // Return no records
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
              Forms\Components\Select::make('section_id')
                ->label('Section')
                ->options(function () {
                    $teacher = Auth::user()->teacher; // Assumes User has a relationship to Teacher
                    return $teacher ? $teacher->sections()->select('sections.id', 'sections.name')->pluck('name', 'id') : [];
                })
                ->searchable()
                ->required()
                ->reactive() // Make section_id reactive to trigger updates in student_id
                ->afterStateUpdated(fn (callable $set) => $set('student_id', null)), // Reset student_id when section changes

            Forms\Components\Select::make('student_id')
                ->label('Student')
                ->options(function () {
                    $guardian = Auth::user()->guardian; // Assumes User has a relationship to Guardian
                    if ($guardian) {
                        return $guardian->students()
                            ->with('user') // Ensure user relationship is loaded
                            ->get()
                            ->pluck('user.name', 'id'); // Display student's name through user relationship
                    }
                    return [];
                })
                ->searchable()
                ->required(),

            Forms\Components\Select::make('subject_id')
                ->label('Subject')
                ->relationship('subject', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('grade')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('remarks')
                ->maxLength(255)
                ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                ->label('Student Name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('subject.name')
                ->label('Subject Name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('student.section.name')
                ->label('Section Name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('grade')
                ->label('Grade')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('remarks')
                ->label('Remarks')
                ->searchable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Updated At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGrades::route('/'),
            'create' => Pages\CreateGrade::route('/create'),
            'edit' => Pages\EditGrade::route('/{record}/edit'),
        ];
    }
}
