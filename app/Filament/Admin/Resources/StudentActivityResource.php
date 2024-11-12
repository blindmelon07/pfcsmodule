<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StudentActivityResource\Pages;
use App\Models\StudentActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentActivityResource extends Resource
{
    protected static ?string $model = StudentActivity::class;

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
                    ->directory('activities')
                    ->disk('public')
                    ->maxSize(10240)
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->downloadable()
                    ->previewable(),

                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => Auth::user()->teacher->id)
                    ->required(),

                Forms\Components\Select::make('section_id')
                    ->label('Section')
                    ->options(function () {
                        $user = Auth::user();

                        return $user && $user->teacher
                            ? $user->teacher->sections()->pluck('name', 'sections.id')
                            : [];
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('student_ids', [])),

                Forms\Components\Select::make('student_ids')
                    ->multiple()
                    ->label('Students')
                    ->options(function (callable $get) {
                        $sectionId = $get('section_id');

                        return $sectionId
                            ? \App\Models\Student::where('section_id', $sectionId)
                                ->with('user')
                                ->get()
                                ->pluck('user.name', 'id')
                            : [];
                    })
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Description')->toggleable(),
                Tables\Columns\TextColumn::make('file_url')
                    ->label('File')
                    ->searchable()
                    ->url(fn ($record) => asset('storage/'.$record->file_url))
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('teacher.user.name')->label('Teacher Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('section.name')->label('Section Name')->sortable()->searchable(),
                // Tables\Columns\TextColumn::make('students_list')
                // ->label('Students')
                // ->getStateUsing(fn ($record) => $record->students->pluck('user.name')->join(', '))
                // ->searchable(), // Keep searchable if you want, but remove sortable()
                Tables\Columns\TextColumn::make('students_list')
                    ->label('Students')
                    ->getStateUsing(function ($record) {
                        // Access the section relationship and then fetch related students
                        return $record->section->students->pluck('user.name')->join(', ');
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->dateTime()->sortable(),
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
            'index' => Pages\ListStudentActivities::route('/'),
            'create' => Pages\CreateStudentActivity::route('/create'),
            'edit' => Pages\EditStudentActivity::route('/{record}/edit'),
        ];
    }
}
