<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ModuleResource\Pages;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Modules';

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

            // If the user has the "guardian" role, filter to show only related students' records
            if ($user->hasRole('guardian')) {
                $guardian = $user->guardian;
                if ($guardian) {
                    return $query->whereHas('student', function (Builder $studentQuery) use ($guardian) {
                        $studentQuery->whereIn('id', $guardian->students->pluck('id'));
                    });
                }

                return $query->whereRaw('0 = 1');
            }

            // If the user has the "teacher" role, filter to show only students in their assigned sections
            if ($user->hasRole('teacher')) {
                $teacher = $user->teacher;
                if ($teacher) {
                    return $query->whereIn('section_id', $teacher->sections->pluck('id'));
                }

                return $query->whereRaw('0 = 1');
            }

            // If the user has the "student" role, filter to show only modules in their section
            if ($user->hasRole('student')) {
                $student = $user->student; // Assuming User model has a 'student' relationship
                if ($student && $student->section_id) {
                    // Filter modules to show only those assigned to the student's section
                    return $query->where('section_id', $student->section_id);
                }

                return $query->whereRaw('0 = 1'); // Return no records if no section is assigned
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
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('file_url')
                    ->label('File')
                    ->required()
                    ->directory('modules')
                    ->disk('public')
                    ->maxSize(10240)
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->downloadable()
                    ->previewable(),

                // Automatically set the logged-in teacher's ID
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => Auth::user()->teacher->id) // Assumes User has a 'teacher' relationship
                    ->required(),

                // Section dropdown that only shows sections assigned to the logged-in teacher
                Forms\Components\Select::make('section_id')
                    ->label('Section')
                    ->options(function () {
                        $user = Auth::user();

                        // Ensure that the user has a 'teacher' relationship
                        if ($user && $user->teacher) {
                            $teacher = $user->teacher;

                            // Fetch sections assigned to the teacher, explicitly selecting 'sections.id' to avoid ambiguity
                            return $teacher->sections()->select('sections.id', 'sections.name')->pluck('name', 'sections.id');
                        }

                        // If no teacher relationship is found, return an empty array
                        return [];
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set) {
                        $set('student_ids', []); // Clear selected students when the section changes
                    }),

                // MultiSelect for students linked to the selected section
                Forms\Components\Select::make('student_ids')
                    ->multiple()
                    ->label('Students')
                    ->options(function (callable $get) {
                        $sectionId = $get('section_id'); // Get the selected section ID
                        if ($sectionId) {
                            return \App\Models\Student::where('section_id', $sectionId)
                                ->with('user') // Load the related user for the student name
                                ->get()
                                ->pluck('user.name', 'id'); // Display student's name through user relationship
                        }

                        return [];
                    })
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('file_url')
                    ->label('File')
                    ->searchable()
                    ->url(fn ($record) => asset('storage/'.$record->file_url))
                    ->openUrlInNewTab(),

                // Display the teacher's name
                Tables\Columns\TextColumn::make('teacher.user.name')
                    ->label('Teacher Name')
                    ->sortable()
                    ->searchable(),

                // Display the section's name
                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section Name')
                    ->sortable()
                    ->searchable(),

                // Display all students associated with this module
                Tables\Columns\TextColumn::make('students_list')
                    ->label('Students')
                    ->getStateUsing(function ($record) {
                        return $record->sectionStudents->pluck('user.name')->join(', ');
                    }),

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
            'index' => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'edit' => Pages\EditModule::route('/{record}/edit'),
        ];
    }
}
