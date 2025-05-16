<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Platform;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Company\Resources\PlatformResource\Pages;
use Filament\Forms\Components\Select;
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
                            ->live()
                            ->afterStateUpdated(function (Set $set, Select $component) {
                                $set('external_id', null);
                                $set('external_name', null);
                                $set('label', null);
                                $set('external_url', null);
                                $set('external_token', null);



                                //Fire the afterStateUpdated event on the external_id select component
                                // This is a workaround to trigger the state update on the select component

                                $select = $component->getContainer()->getComponent('external_id');
                                $select->state(array_key_first($select->getOptions()))
                                    ->callAfterStateUpdated();
                            }),
                        Forms\Components\Select::make('external_id')
                            ->label('Page / Account')
                            ->options(function(Get $get) {
                                return match ($get('provider')) {
                                    'facebook' => (new \App\Services\FacebookService(auth()->user()))->listPages(),
                                    'instagram' => (new \App\Services\InstagramService(auth()->user()))->listAccounts(),
                                    'x' => app(\App\Services\XService::class)->listAccounts(),
                                    default => [],
                                };
                            })
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live()
                            ->key('external_id')
                            ->afterStateUpdated(function ($state, Set $set, Get $get,) {
                                match ($get('provider')) {
                                    'facebook' => (new \App\Services\FacebookService(auth()->user()))->fillPageDetails($state, $set),
                                    'instagram' => (new \App\Services\InstagramService(auth()->user()))->fillAccountDetails($state, $set),
                                    'x' => app(\App\Services\XService::class)->fillAccountDetails($state, $set),
                                    default => null,
                                };
                            })
                            ->disabled(fn (Get $get): bool => blank($get('provider')))
                            ->dehydrated(fn (Get $get): bool => filled($get('provider'))),
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Label')
                            ->disabled(fn (Get $get): bool => blank($get('provider')))
                            ->dehydrated(fn (Get $get): bool => filled($get('provider'))),
                        Forms\Components\Hidden::make('external_name'),
                        Forms\Components\Hidden::make('external_url'),
                        Forms\Components\Hidden::make('external_token'),
                        Forms\Components\Hidden::make('external_picture_url'),
                    ])->columns(1)->maxWidth('lg'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('external_picture_url')
                    ->label('')
                    ->size(30)
                    ->circular(),
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
