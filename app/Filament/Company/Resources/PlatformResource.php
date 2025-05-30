<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Platform;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Company\Resources\PlatformResource\Pages;
use App\Support\ImageStore;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Filament\Company\Resources\PlatformResource\Actions\DeletePlatformAction;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 10;

    // protected static ?string $tenantRelationshipName = "company";

    public static function form(Form $form): Form
    {
        // Check if we're in edit mode
        $isEditMode = $form->getRecord() !== null;

        // If in edit mode, only show the label field
        if ($isEditMode) {
            return $form
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('label')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->label('Label'),
                        ])->columns(1)->maxWidth('lg'),
                ]);
        }

        // Otherwise, show the full form for creating a new platform
        return $form
            ->schema([
                // Show message when no providers are available
                Forms\Components\Placeholder::make('no_providers_message')
                    ->label('')
                    ->content('Your company already has all available platform types (Facebook, Instagram, X). You can only have one platform per type.')
                    ->visible(function () {
                        return empty(self::getAvailableProviders());
                    }),

                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->label('Provider')
                            ->options(function () {
                                return self::getAvailableProviders();
                            })
                            ->placeholder(function () {
                                $availableProviders = self::getAvailableProviders();
                                if (empty($availableProviders)) {
                                    return 'Your company already has all available platform types';
                                }
                                return 'Select a platform type';
                            })
                            ->required()
                            ->disabled(function () {
                                return empty(self::getAvailableProviders());
                            })
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
                            ->options(function (Get $get) {
                                return match ($get('provider')) {
                                    'facebook', 'instagram', 'x' => self::getAvailablePlatforms($get('provider')),
                                    default => [],
                                };
                            })
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live()
                            ->key('external_id')
                            ->afterStateUpdated(function ($state, Set $set, Get $get,) {
                                self::fillPlatformDetails($state, $set, $get('provider'));
                            })
                            ->disabled(fn(Get $get): bool => blank($get('provider')))
                            ->dehydrated(fn(Get $get): bool => filled($get('provider'))),
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->label('Label')
                            ->disabled(fn(Get $get): bool => blank($get('provider')))
                            ->dehydrated(fn(Get $get): bool => filled($get('provider'))),
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
                    ->getStateUsing(function ($record) {
                        return $record->external_picture_url ? ImageStore::url($record->external_picture_url) : null;
                    })
                    ->circular(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Custom Label')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Type')
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
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;
                        return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;
                        return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    DeletePlatformAction::forTable(),
                ])->dropdown(true)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Remove Selected'),
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
            'create' => Pages\CreatePlatform::route('/add'),
            'edit' => Pages\EditPlatform::route('/{record}/edit'),
        ];
    }

    /**
     * Get available providers for the current company
     * Only returns providers that the company doesn't already have
     *
     * @return array Array of provider options [provider => label]
     */
    public static function getAvailableProviders(): array
    {
        // All possible providers
        $allProviders = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'x' => 'X (Twitter)',
        ];

        // Get the current company ID
        $companyId = \Filament\Facades\Filament::getTenant()->id;

        // Get providers that the company already has
        $existingProviders = \App\Models\Platform::where('company_id', $companyId)
            ->pluck('provider')
            ->toArray();

        // Return only providers that the company doesn't have
        return array_diff_key($allProviders, array_flip($existingProviders));
    }

    /**
     * Get available platforms for the current company
     *
     * @param string $provider The platform provider (facebook, instagram, x)
     * @return array Array of platform options [external_id => external_name]
     */
    public static function getAvailablePlatforms(string $provider): array
    {
        // Get the current company ID
        $companyId = \Filament\Facades\Filament::getTenant()->id;

        // Check if company already has a platform of this type
        $hasExistingPlatform = \App\Models\Platform::where('company_id', $companyId)
            ->where('provider', $provider)
            ->exists();

        // If company already has this platform type, return empty array
        if ($hasExistingPlatform) {
            return [];
        }

        // Get all platforms of this provider that don't have a company_id (unclaimed)
        $availablePlatforms = \App\Models\Platform::where('provider', $provider)
            ->whereNull('company_id')
            ->pluck('external_name', 'external_id')
            ->toArray();

        return $availablePlatforms;
    }

    /**
     * Fill platform details from the database
     *
     * @param string $platformId The external ID of the platform
     * @param Set $set The Filament form state setter
     * @param string $provider The platform provider (facebook, instagram, x)
     */
    public static function fillPlatformDetails(string $platformId, Set $set, string $provider): void
    {
        // Get the platform details from the database
        $platform = \App\Models\Platform::where('external_id', $platformId)
            ->where('provider', $provider)
            ->first();

        if ($platform) {
            $set('label', $platform->external_name);
        }
    }
}
