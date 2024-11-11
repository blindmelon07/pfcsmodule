<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GradeResource\Pages;
use App\Models\Grade;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Modules';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->check()) {
            $user = auth()->user();

            if ($user->hasRole('super_admin')) {
                return $query;
            }

            if ($user->hasRole('teacher')) {
                $teacher = $user->teacher;
                if ($teacher) {
                    return $query->whereHas('student', function (Builder $studentQuery) use ($teacher) {
                        $studentQuery->whereIn('section_id', $teacher->sections->pluck('id'));
                    });
                }

                return $query->whereRaw('0 = 1');
            }

            if ($user->hasRole('guardian')) {
                $guardian = $user->guardian;
                if ($guardian) {
                    return $query->whereHas('student', function (Builder $studentQuery) use ($guardian) {
                        $studentQuery->whereIn('id', $guardian->students->pluck('id'));
                    });
                }

                return $query->whereRaw('0 = 1');
            }

            if ($user->hasRole('student')) {
                $student = $user->student;
                if ($student) {
                    return $query->where('student_id', $student->id);
                }

                return $query->whereRaw('0 = 1');
            }

            return $query->whereRaw('0 = 1');
        }

        return $query->whereRaw('0 = 1');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('section_id')
                    ->label('Section')
                    ->options(function () {
                        $teacher = Auth::user()->teacher;

                        return $teacher ? $teacher->sections()->pluck('name', 'sections.id') : [];
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('student_id', null)),

                Forms\Components\Select::make('student_id')
                    ->label('Student')
                    ->options(function (callable $get) {
                        $sectionId = $get('section_id');
                        if ($sectionId) {
                            return \App\Models\Student::where('section_id', $sectionId)
                                ->with('user')
                                ->get()
                                ->pluck('user.name', 'id');
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

                TextInput::make('first_quarter')
                    ->numeric()
                    ->label('First Quarter'),
                TextInput::make('second_quarter')
                    ->numeric()
                    ->label('Second Quarter'),
                TextInput::make('third_quarter')
                    ->numeric()
                    ->label('Third Quarter'),
                TextInput::make('fourth_quarter')
                    ->numeric()
                    ->label('Fourth Quarter'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('Student Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('subject.name')
                    ->label('Subject Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('student.section.name')
                    ->label('Section Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('first_quarter')->label('First Quarter'),
                TextColumn::make('second_quarter')->label('Second Quarter'),
                TextColumn::make('third_quarter')->label('Third Quarter'),
                TextColumn::make('fourth_quarter')->label('Fourth Quarter'),

                // Calculate average score across quarters
                TextColumn::make('score')
                    ->label('Average Score')
                    ->getStateUsing(fn ($record) => round(collect([
                        $record->first_quarter,
                        $record->second_quarter,
                        $record->third_quarter,
                        $record->fourth_quarter,
                    ])->filter()->avg(), 2))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
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
