<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ArchivedModuleResource\Pages;
use App\Models\Module; // Ensure it's using the correct model
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArchivedModuleResource extends Resource
{
    protected static ?string $model = Module::class; // Ensure this is set to Module

    protected static ?string $navigationLabel = 'Archived Modules';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Administration';

    public static function getEloquentQuery(): Builder
    {
        // Fetch only soft-deleted (archived) records for the Module model
        return parent::getEloquentQuery()->onlyTrashed();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Define the fields as disabled since they’re archived
                TextInput::make('title')->disabled(),
                Textarea::make('description')->disabled(),
                FileUpload::make('file_url')->label('File')->disabled(),
                Select::make('section_id')->label('Section')->relationship('section', 'name')->disabled(),
                Select::make('teacher_id')->label('Teacher')->relationship('teacher.user', 'name')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Description')->limit(50),
                Tables\Columns\TextColumn::make('file_url')
                    ->label('File')
                    ->url(fn ($record) => asset('storage/'.$record->file_url))
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('section.name')->label('Section'),
                Tables\Columns\TextColumn::make('teacher.user.name')->label('Teacher'),
                Tables\Columns\TextColumn::make('deleted_at')->label('Archived At')->dateTime()->sortable(),
            ])
            ->actions([
                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Module $record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()->title('Module restored successfully.')->success()->send();
                    })
                    ->visible(fn ($record) => $record->trashed()), // Only show restore if trashed
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchivedModules::route('/'),
        ];
    }
}
