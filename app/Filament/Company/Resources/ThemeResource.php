<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\ThemeResource\Pages;
use App\Helpers\FontHelper;
use App\Models\Theme;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Themes';

    protected static ?string $navigationGroup = 'Image Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Theme Settings')
                    ->columnSpanFull()
                    ->tabs([
                        // Tab 1: General Information
                        Tab::make('General')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),

                                                Toggle::make('is_default')
                                                    ->label('Set as default theme')
                                                    ->helperText('If enabled, this theme will be selected by default when creating new templates')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state) {
                                                        if ($state) {
                                                            // If setting this theme as default, we need to unset any other default
                                                            $companyId = Auth::user()->currentCompany->id;
                                                            Theme::where('company_id', $companyId)
                                                                ->where('is_default', true)
                                                                ->update(['is_default' => false]);
                                                        }
                                                    }),
                                            ]),
                                    ]),
                            ]),
                        // Tab 2: Typography
                        Tab::make('Typography')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Select::make('fonts')
                                                    ->label('Fonts')
                                                    ->multiple()
                                                    ->options(FontHelper::getStyledFontOptions())
                                                    ->searchable()
                                                    ->allowHtml()
                                                    ->helperText('Select fonts available in the system.'),
                                            ]),

                                        Forms\Components\Group::make()
                                            ->schema([
                                                Repeater::make('font_sizes')
                                                    ->label('Font Sizes')
                                                    ->schema([
                                                        TextInput::make('size')
                                                            ->required()
                                                            ->numeric()
                                                            ->label('Size (px)')
                                                            ->minValue(48)
                                                            ->maxValue(140)
                                                            ->helperText('Font sizes should be between 48px and 140px for optimal display'),

                                                        TextInput::make('name')
                                                            ->required()
                                                            ->label('Display Name')
                                                            ->placeholder('e.g., "Medium", "Large", etc.'),
                                                    ])
                                                    ->columns(2)
                                                    ->defaultItems(0)
                                                    ->default([
                                                        ['size' => '48', 'name' => 'Small'],
                                                        ['size' => '64', 'name' => 'Medium'],
                                                        ['size' => '80', 'name' => 'Large'],
                                                        ['size' => '96', 'name' => 'Extra Large'],
                                                        ['size' => '112', 'name' => 'Huge'],
                                                        ['size' => '140', 'name' => 'Giant'],
                                                    ])
                                                    ->collapsed()
                                                    ->itemLabel(fn(array $state): ?string => ($state['name'] ?? '') . ' (' . ($state['size'] ?? '') . 'px)')
                                                    ->addActionLabel('Add Font Size')
                                                    ->helperText('Add custom font sizes that will be available in image templates'),
                                            ]),
                                    ]),
                            ]),

                        // Tab 3: Colors
                        Tab::make('Colors')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Repeater::make('colors')
                                                    ->label('Color Palette')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->required()
                                                            ->label('Color Name'),

                                                        ColorPicker::make('value')
                                                            ->required()
                                                            ->label('Color Value'),
                                                    ])
                                                    ->columns(2)
                                                    ->defaultItems(0)
                                                    ->default([
                                                        ['name' => 'White', 'value' => '#ffffff'],
                                                        ['name' => 'Black', 'value' => '#000000'],
                                                        ['name' => 'Slate 900', 'value' => '#0f172a'],
                                                        ['name' => 'Red 500', 'value' => '#ef4444'],
                                                        ['name' => 'Green 500', 'value' => '#22c55e'],
                                                        ['name' => 'Blue 500', 'value' => '#3b82f6'],
                                                        ['name' => 'Yellow 500', 'value' => '#eab308'],
                                                        ['name' => 'Purple 500', 'value' => '#a855f7'],
                                                        ['name' => 'Pink 500', 'value' => '#ec4899'],
                                                        ['name' => 'Teal 500', 'value' => '#14b8a6'],
                                                    ])
                                                    ->collapsed()
                                                    ->itemLabel(fn(array $state): ?string => ($state['name'] ?? 'Unnamed') . ' - ' . ($state['value'] ?? ''))
                                                    ->addActionLabel('Add Color'),
                                            ]),
                                    ]),
                            ]),

                        // Tab 4: Layout
                        Tab::make('Layout')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Repeater::make('paddings')
                                                    ->label('Padding')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->required()
                                                            ->label('Padding Name'),

                                                        TextInput::make('value')
                                                            ->required()
                                                            ->numeric()
                                                            ->label('Padding Value (px)'),
                                                    ])
                                                    ->columns(2)
                                                    ->defaultItems(0)
                                                    ->default([
                                                        ['name' => 'Small', 'value' => '16'],
                                                        ['name' => 'Medium', 'value' => '32'],
                                                        ['name' => 'Large', 'value' => '64'],
                                                    ])
                                                    ->collapsed()
                                                    ->itemLabel(fn(array $state): ?string => ($state['name'] ?? 'Unnamed') . ' (' . ($state['value'] ?? '') . 'px)')
                                                    ->addActionLabel('Add Padding'),
                                            ]),
                                    ]),
                            ]),

                        // Tab 5: Backgrounds
                        Tab::make('Backgrounds')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Repeater::make('background_images')
                                                    ->label('Background Images')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->required()
                                                            ->label('Image Name'),

                                                        FileUpload::make('value')
                                                            ->required()
                                                            ->image()
                                                            ->directory('backgrounds')
                                                            ->disk('public')
                                                            ->visibility('public')
                                                            ->imagePreviewHeight('250')
                                                            ->panelAspectRatio('16:9')
                                                            ->panelLayout('integrated')
                                                            ->downloadable()
                                                            ->openable()
                                                            ->acceptedFileTypes([
                                                                'image/jpeg',
                                                                'image/jpg',
                                                                'image/png',
                                                                'image/gif',
                                                                'image/webp',
                                                                'image/svg+xml',
                                                                'image/bmp',
                                                                'image/tiff'
                                                            ])
                                                            ->label('Image File'),
                                                    ])
                                                    ->columns(2)
                                                    ->collapsible()
                                                    ->persistCollapsed(false)
                                                    ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                                                    ->addActionLabel('Add Background Image'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                Tables\Columns\TextColumn::make('fonts')
                    ->label('Fonts')
                    ->formatStateUsing(function ($record) {
                        if (!$record instanceof Theme) {
                            return 'No fonts available';
                        }

                        // Get fonts directly from the model to ensure proper casting
                        $fonts = $record->fonts;

                        // Check if the array is empty
                        if (empty($fonts)) {
                            return 'No fonts available';
                        }

                        $count = count($fonts);
                        $fontList = '';

                        // Show first 3 fonts with preview
                        $fontOptions = FontHelper::getStyledFontOptions();
                        $showCount = min(3, $count);
                        for ($i = 0; $i < $showCount; $i++) {
                            if (isset($fonts[$i])) {
                                $fontKey = $fonts[$i];
                                $fontHtml = $fontOptions[$fontKey] ?? $fontKey;
                                $fontList .= "<div class='mb-1'>{$fontHtml}</div>";
                            }
                        }

                        // If there are more fonts, show a count
                        if ($count > 3) {
                            $fontList .= "<div class='text-xs text-gray-500'>+" . ($count - 3) . " more</div>";
                        }

                        return "{$fontList}";
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('colors')
                    ->label('Colors')
                    ->formatStateUsing(function ($record) {
                        if (!$record instanceof Theme) {
                            return 'No colors available';
                        }

                        // Get colors directly from the model to ensure proper casting
                        $colors = $record->colors;

                        // Check if the array is empty
                        if (empty($colors)) {
                            return 'No colors available';
                        }

                        $count = count($colors);
                        $colorSwatches = '<div class="flex flex-wrap gap-1 mt-1">';

                        // Show color swatches for all colors (up to 10)
                        $showCount = min(10, $count);
                        for ($i = 0; $i < $showCount; $i++) {
                            if (isset($colors[$i]['value'])) {
                                $colorValue = $colors[$i]['value'];
                                $colorName = $colors[$i]['name'] ?? '';
                                $colorSwatches .= "<div title='{$colorName}: {$colorValue}' style='width:16px; height:16px; background-color:{$colorValue}; border-radius:2px; border:1px solid rgba(0,0,0,0.1);'></div>";
                            }
                        }

                        // If there are more colors, show a count
                        if ($count > 10) {
                            $colorSwatches .= "<div class='text-xs text-gray-500 ml-1'>+" . ($count - 10) . " more</div>";
                        }

                        $colorSwatches .= '</div>';

                        return "{$colorSwatches}";
                    })
                    ->html(),

                ImageColumn::make('background_images')
                    ->label('Backgrounds')
                    ->getStateUsing(function ($record): array {
                        return array_column($record->background_images ?? [], 'value');
                    })
                    ->size(40)
                    ->square()
                    ->stacked()
                    ->limit(5),

                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;
                        return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;
                        return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                    })->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListThemes::route('/'),
            'create' => Pages\CreateTheme::route('/create'),
            'edit' => Pages\EditTheme::route('/{record}/edit'),
        ];
    }
}
