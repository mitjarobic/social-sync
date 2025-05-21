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
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->required(),
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
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->action(function ($record) {
                        $record->update([
                            'is_active' => ! $record->is_active,
                        ]);
                    })
                    ->tooltip(fn($record) => $record->is_active ? 'Click to deactivate' : 'Click to activate')
                    ->sortable()
                    ->boolean(),
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
                \Filament\Tables\Filters\Filter::make('active')
                    ->label('Active Only')
                    ->toggle()
                    ->default(true)
                    ->query(fn($query) => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    DeletePlatformAction::forTable(),
                ])->dropdown(true)
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

    /**
     * Get available platforms for the current company
     *
     * @param string $provider The platform provider (facebook, instagram, x)
     * @return array Array of platform options [external_id => external_name]
     */
    public static function getAvailablePlatforms(string $provider): array
    {
        // Get the current company ID
        $companyId = request()->user()->currentCompany->id;

        // Get existing platform IDs for this company
        $existingPlatformIds = \App\Models\Platform::where('company_id', $companyId)
            ->where('provider', $provider)
            ->pluck('external_id')
            ->toArray();

        // Get all platforms of this provider that don't belong to the current company
        $availablePlatforms = \App\Models\Platform::where('provider', $provider)
            ->whereNotIn('external_id', $existingPlatformIds)
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
