<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GuardianResource\Pages;
use App\Filament\Admin\Resources\GuardianResource\RelationManagers;
use App\Models\Guardian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Select::make('user_id')
                ->label('Guardian')
                ->options(\App\Models\User::whereHas('roles', function ($query) {
                    $query->where('name', 'guardian');
                })->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('student_ids')
                ->label('Students')
                ->multiple()
                ->options(\App\Models\User::whereHas('roles', function ($query) {
                    $query->where('name', 'student');
                })->pluck('name', 'id'))
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                ->label('Guardian Name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('students_list')
                ->label('Students')
                ->getStateUsing(function ($record) {
                    return $record->students->pluck('user.name')->join(', ');
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListGuardians::route('/'),
            'create' => Pages\CreateGuardian::route('/create'),
            'edit' => Pages\EditGuardian::route('/{record}/edit'),
        ];
    }
}
