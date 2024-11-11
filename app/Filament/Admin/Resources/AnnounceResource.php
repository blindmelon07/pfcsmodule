<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AnnounceResource\Pages;
use App\Models\Announce;
use Filament\Forms;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnnounceResource extends Resource
{
    protected static ?string $model = Announce::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Announcement';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Announcement Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Announcement Title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('body')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->rows(4),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Upload Image')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Announcement Image')
                            ->required()
                            ->image()
                        // ->imagePreviewHeight('0')
                        , // Hides the default preview

                        // Pass the record to the custom view component
                        View::make('components.enlarge-image')->viewData([
                            'imageUrl' => fn ($get) => $get('image') ? asset('storage/'.$get('image')) : null,
                        ]),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('body')->searchable(),
                Tables\Columns\ImageColumn::make('image')->disk('public'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),

            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')->label('From'),
                        Forms\Components\DatePicker::make('end_date')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['start_date'], fn (Builder $query, $date) => $query->where('created_at', '>=', $date))
                            ->when($data['end_date'], fn (Builder $query, $date) => $query->where('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListAnnounces::route('/'),
            'create' => Pages\CreateAnnounce::route('/create'),
            'view' => Pages\ViewAnnounce::route('/{record}'),
            'edit' => Pages\EditAnnounce::route('/{record}/edit'),
        ];
    }
}
