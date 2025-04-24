<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Platform;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\PlatformResource\Pages;
use App\Filament\Company\Resources\PlatformResource\RelationManagers;
use Filament\Forms\Get;
use Filament\Forms\Set;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $tenantRelationshipName = "company";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->label('Provider')
                            ->options([
                                'facebook' => 'Facebook',
                                'instagram' => 'Instagram',
                                'x' => 'X (Twitter)',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('external_id')
                            ->label('Page / Account')
                            ->options(fn(Get $get) => match ($get('provider')) {
                                'facebook' => app(\App\Services\FacebookService::class)->listPages(),
                                default => [],
                            })
                            ->selectablePlaceholder(false)
                            ->placeholder('Select a page or account')
                            ->loadingMessage('Loading pages or accounts ...')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn($state, Set $set, Get $get) => match ($get('provider')) {
                                'facebook' => app(\App\Services\FacebookService::class)->fillPageDetails($state, $set),
                                default => null,
                            }),
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Label'),
                        Forms\Components\TextInput::make('external_name')
                            ->label('External Name')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('external_url')
                            ->label('External URL')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Hidden::make('external_token'), // storing access token

                    ])->columns(1)->maxWidth('lg'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Label')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Platform')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('external_name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('external_url')
                    ->label('Url')
                    ->formatStateUsing(fn() => 'Link')
                    ->url(fn($record) => $record->external_url)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListPlatforms::route('/'),
            'create' => Pages\CreatePlatform::route('/create'),
            'edit' => Pages\EditPlatform::route('/{record}/edit'),
        ];
    }
}
