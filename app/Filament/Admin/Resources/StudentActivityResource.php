<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StudentActivityResource\Pages;
use App\Models\StudentActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StudentActivityResource extends Resource
{
    protected static ?string $model = StudentActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('section_id')
                    ->label('Section')
                    ->relationship('section', 'name') // Assuming there's a relationship to the Section model
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('student_id', null)),
                Forms\Components\Select::make('student_id')
                ->label('Student')
                ->options(function () {
                    return \App\Models\Student::with('user') // Ensure user relationship is loaded
                        ->get()
                        ->pluck('user.name', 'id'); // Access the name through the user relationship
                })
                ->required()
                ->searchable(),

                 // Reset student selection on section change
                 Tables\Columns\TextColumn::make('teacher.user.name')
                 ->label('Teacher Name')
                 ->sortable()
                 ->searchable(),

                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('file_url')
                    ->label('File')
                    ->directory('student_activities')
                    ->disk('public')
                    ->required()
                    ->maxSize(10240) // Limit file size
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->downloadable()
                    ->previewable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section.name')
                ->label('Section Name')
                ->sortable()
                ->searchable(),
            
            Tables\Columns\TextColumn::make('student.user.name') // Accessing student's name through the user relationship
                ->label('Student Name')
                ->sortable()
                ->searchable(),
            
            Tables\Columns\TextColumn::make('teacher.user.name') // Accessing teacher's name through the user relationship
                ->label('Teacher Name')
                ->sortable()
                ->searchable(),
            
            Tables\Columns\TextColumn::make('title')
                ->label('Title')
                ->searchable(),
            
            Tables\Columns\TextColumn::make('file_url')
                ->label('File')
                ->url(fn ($record) => asset('storage/' . $record->file_url))
                ->openUrlInNewTab(),
            
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
                Tables\Actions\DeleteBulkAction::make(),
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
