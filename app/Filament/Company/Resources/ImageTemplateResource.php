<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Theme;
use Filament\Forms\Form;

use Filament\Tables\Table;
use App\Helpers\FontHelper;
use App\Support\ImageStore;
use App\Models\ImageTemplate;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use App\Filament\Company\Resources\ImageTemplateResource\Pages;

class ImageTemplateResource extends Resource
{
    protected static ?string $model = ImageTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Image Templates';

    protected static ?string $navigationGroup = 'Image Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Theme Settings')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Template Information')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([

                                        Forms\Components\Group::make()
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),

                                                Select::make('theme_id')
                                                    ->label('Theme')
                                                    ->options(function () {
                                                        $themes = Theme::where('company_id', Auth::user()->currentCompany->id)->get();
                                                        if ($themes->isEmpty()) {
                                                            return [];
                                                        }
                                                        return $themes->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->reactive()
                                                    ->selectablePlaceholder(false)
                                                    ->default(function () {
                                                        // First try to get the default theme
                                                        $defaultTheme = Theme::where('company_id', Auth::user()->currentCompany->id)
                                                            ->where('is_default', true)
                                                            ->first();

                                                        if ($defaultTheme) {
                                                            return $defaultTheme->id;
                                                        }

                                                        // If no default theme, get the first theme
                                                        $firstTheme = Theme::where('company_id', Auth::user()->currentCompany->id)
                                                            ->first();

                                                        return $firstTheme?->id;
                                                    })
                                                    ->helperText(function () {
                                                        $count = Theme::where('company_id', Auth::user()->currentCompany->id)->count();
                                                        if ($count === 0) {
                                                            return 'You must create at least one theme before creating a template. Go to Themes to create one.';
                                                        }
                                                        return 'Select a theme to use for this template';
                                                    })
                                                    ->afterStateUpdated(function (callable $set, $state) {
                                                        // Reset all values first
                                                        $set('font_family', null);
                                                        $set('font_size', null);
                                                        $set('font_color', null);
                                                        $set('background_color', null);
                                                        $set('padding', null);
                                                        $set('background_image', null);

                                                        // Reset content and author font settings
                                                        $set('content_font_family', null);
                                                        $set('content_font_size', null);
                                                        $set('content_font_color', null);
                                                        $set('author_font_family', null);
                                                        $set('author_font_size', null);
                                                        $set('author_font_color', null);

                                                        if ($state) {
                                                            $theme = Theme::find($state);
                                                            if ($theme) {
                                                                // Only set values if they exist in the theme
                                                                if (!empty($theme->fonts) && is_array($theme->fonts) && count($theme->fonts) > 0) {
                                                                    $set('font_family', $theme->fonts[0]);
                                                                    $set('content_font_family', $theme->fonts[0]);
                                                                    $set('author_font_family', $theme->fonts[0]);
                                                                }

                                                                if (!empty($theme->font_sizes) && is_array($theme->font_sizes) && count($theme->font_sizes) > 0) {
                                                                    $set('font_size', $theme->font_sizes[0]['size'] ?? null);
                                                                    $set('content_font_size', $theme->font_sizes[0]['size'] ?? null);

                                                                    // For author font size, use a smaller size if available
                                                                    if (count($theme->font_sizes) > 1) {
                                                                        $set('author_font_size', $theme->font_sizes[1]['size'] ?? null);
                                                                    } else {
                                                                        $set('author_font_size', $theme->font_sizes[0]['size'] ?? null);
                                                                    }
                                                                }

                                                                if (!empty($theme->colors) && is_array($theme->colors) && count($theme->colors) > 0) {
                                                                    $set('font_color', $theme->colors[0]['value'] ?? null);
                                                                    $set('content_font_color', $theme->colors[0]['value'] ?? null);
                                                                    $set('author_font_color', $theme->colors[0]['value'] ?? null);

                                                                    if (count($theme->colors) > 1) {
                                                                        $set('background_color', $theme->colors[1]['value'] ?? null);
                                                                    }
                                                                }

                                                                if (!empty($theme->paddings) && is_array($theme->paddings) && count($theme->paddings) > 0) {
                                                                    $set('padding', $theme->paddings[0]['value'] ?? null);
                                                                }
                                                            }
                                                        }
                                                    }),

                                                Toggle::make('is_default')
                                                    ->label('Set as default template')
                                                    ->helperText('If enabled, this template will be selected by default when creating new posts')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state) {
                                                        if ($state) {
                                                            // If setting this template as default, we need to unset any other default
                                                            $companyId = Auth::user()->currentCompany->id;
                                                            ImageTemplate::where('company_id', $companyId)
                                                                ->where('is_default', true)
                                                                ->update(['is_default' => false]);
                                                        }
                                                    }),
                                            ])
                                    ])
                            ]),
                        Forms\Components\Tabs\Tab::make('Background Settings')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Radio::make('background_type')
                                                    ->label('Background Type')
                                                    ->options([
                                                        'color' => 'Solid Color',
                                                        'image' => 'Image',
                                                    ])
                                                    ->default(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return 'color';
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme) {
                                                            return 'color';
                                                        }

                                                        // If the theme has background images, suggest using an image
                                                        if (!empty($theme->background_images) && is_array($theme->background_images) && count($theme->background_images) > 0) {
                                                            return 'image';
                                                        }

                                                        // Otherwise default to color
                                                        return 'color';
                                                    })
                                                    ->reactive(),

                                                Select::make('background_color')
                                                    ->label('Background Color')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->colors) || !is_array($theme->colors)) {
                                                            return [];
                                                        }

                                                        $colorOptions = [];
                                                        foreach ($theme->colors as $colorData) {
                                                            if (isset($colorData['name']) && isset($colorData['value'])) {
                                                                $colorOptions[$colorData['value']] = $colorData['name'];
                                                            }
                                                        }

                                                        return $colorOptions;
                                                    })
                                                    ->visible(fn(callable $get) => $get('background_type') === 'color' && !empty($get('theme_id')))
                                                    ->required(fn(callable $get) => $get('background_type') === 'color')
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && $get('background_type') === 'color' && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->colors) && is_array($theme->colors) && count($theme->colors) > 1) {
                                                                if (isset($theme->colors[1]['value'])) {
                                                                    $set('background_color', $theme->colors[1]['value']);
                                                                } else if (isset($theme->colors[0]['value'])) {
                                                                    $set('background_color', $theme->colors[0]['value']);
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false)
                                                    ->allowHtml(),

                                                Select::make('background_image')
                                                    ->label('Background Image')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) return [];

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->background_images) || !is_array($theme->background_images)) return [];

                                                        $imageOptions = [];
                                                        foreach ($theme->background_images as $image) {
                                                            if (isset($image['name']) && isset($image['value'])) {
                                                                $imageOptions[$image['value']] = $image['name'];
                                                            }
                                                        }

                                                        return $imageOptions;
                                                    })
                                                    ->visible(fn(callable $get) => $get('background_type') === 'image' && !empty($get('theme_id')))
                                                    ->required(fn(callable $get) => $get('background_type') === 'image')
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && $get('background_type') === 'image' && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->background_images) && is_array($theme->background_images) && count($theme->background_images) > 0) {
                                                                if (isset($theme->background_images[0]['value'])) {
                                                                    $set('background_image', $theme->background_images[0]['value']);
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->dehydrated(fn(callable $get) => $get('background_type') === 'image')
                                                    ->default(function (callable $get) {
                                                        if ($get('background_type') !== 'image' || empty($get('theme_id'))) {
                                                            return null;
                                                        }

                                                        $theme = Theme::find($get('theme_id'));
                                                        if (!$theme || empty($theme->background_images) || !is_array($theme->background_images)) {
                                                            return null;
                                                        }

                                                        if (count($theme->background_images) > 0 && isset($theme->background_images[0]['value'])) {
                                                            return $theme->background_images[0]['value'];
                                                        }

                                                        return null;
                                                    })
                                                    ->selectablePlaceholder(false),
                                            ])
                                    ])
                            ]),
                        Forms\Components\Tabs\Tab::make('General Text Settings')
                            ->schema([

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Select::make('text_alignment')
                                                    ->label('Text Alignment')
                                                    ->options([
                                                        'left' => 'Left',
                                                        'center' => 'Center',
                                                        'right' => 'Right',
                                                    ])
                                                    ->default('center')
                                                    ->selectablePlaceholder(false),

                                                Select::make('text_position')
                                                    ->label('Text Position')
                                                    ->options([
                                                        'top' => 'Top',
                                                        'middle' => 'Middle',
                                                        'bottom' => 'Bottom',
                                                    ])
                                                    ->default('middle')
                                                    ->selectablePlaceholder(false),


                                                Select::make('padding')
                                                    ->label('Padding')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->paddings) || !is_array($theme->paddings)) {
                                                            return [];
                                                        }

                                                        $paddingOptions = [];
                                                        foreach ($theme->paddings as $paddingData) {
                                                            if (isset($paddingData['name']) && isset($paddingData['value'])) {
                                                                $paddingOptions[$paddingData['value']] = $paddingData['name'] . ' (' . $paddingData['value'] . 'px)';
                                                            }
                                                        }

                                                        return $paddingOptions;
                                                    })
                                                    ->visible(fn(callable $get) => !empty($get('theme_id')))
                                                    ->required()
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->paddings) && is_array($theme->paddings) && count($theme->paddings) > 0) {
                                                                if (isset($theme->paddings[0]['value'])) {
                                                                    $set('padding', $theme->paddings[0]['value']);
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Content Text Settings')
                            ->schema([

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Select::make('content_font_family')
                                                    ->label('Font Family')

                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->fonts) || !is_array($theme->fonts)) {
                                                            return [];
                                                        }

                                                        $fonts = collect(FontHelper::getAvailableFonts())->only($theme->fonts)->all();
                                                        return FontHelper::getStyledFontOptions($fonts);
                                                    })
                                                    ->native(false)
                                                    ->visible(fn(callable $get) => !empty($get('theme_id')))
                                                    ->required()
                                                    ->allowHtml()
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->fonts) && is_array($theme->fonts) && count($theme->fonts) > 0) {
                                                                $set('content_font_family', $theme->fonts[0]);
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false),

                                                Select::make('content_font_size')
                                                    ->label('Font Size')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->font_sizes) || !is_array($theme->font_sizes)) {
                                                            return [];
                                                        }

                                                        $sizeOptions = [];
                                                        foreach ($theme->font_sizes as $sizeData) {
                                                            if (isset($sizeData['size']) && isset($sizeData['name'])) {
                                                                $sizeOptions[$sizeData['size']] = $sizeData['name'] . ' (' . $sizeData['size'] . 'px)';
                                                            }
                                                        }

                                                        return $sizeOptions;
                                                    })
                                                    ->visible(fn(callable $get) => !empty($get('theme_id')))
                                                    ->required()
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->font_sizes) && is_array($theme->font_sizes) && count($theme->font_sizes) > 0) {
                                                                if (isset($theme->font_sizes[0]['size'])) {
                                                                    $set('content_font_size', $theme->font_sizes[0]['size']);
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false),

                                                Select::make('content_font_color')
                                                    ->label('Font Color')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->colors) || !is_array($theme->colors)) {
                                                            return [];
                                                        }

                                                        $colorOptions = [];
                                                        foreach ($theme->colors as $colorData) {
                                                            if (isset($colorData['name']) && isset($colorData['value'])) {
                                                                $colorOptions[$colorData['value']] = '<div style="display: flex; align-items: center;"><span style="display: inline-block; width: 16px; height: 16px; background-color: ' . $colorData['value'] . '; border-radius: 4px; margin-right: 8px;"></span>' . $colorData['name'] . '</div>';
                                                            }
                                                        }

                                                        return $colorOptions;
                                                    })
                                                    ->visible(fn(callable $get) => !empty($get('theme_id')))
                                                    ->required()
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->colors) && is_array($theme->colors) && count($theme->colors) > 0) {
                                                                if (isset($theme->colors[0]['value'])) {
                                                                    $set('content_font_color', $theme->colors[0]['value']);
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false)
                                                    ->allowHtml(),
                                            ]),
                                    ]),

                            ]),
                        Forms\Components\Tabs\Tab::make('Author Text Settings')
                            ->schema([

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Select::make('author_font_family')
                                                    ->label('Font Family')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->fonts) || !is_array($theme->fonts)) {
                                                            return [];
                                                        }

                                                        $fonts = collect(FontHelper::getAvailableFonts())->only($theme->fonts)->all();
                                                        return FontHelper::getStyledFontOptions($fonts);
                                                    })
                                                    ->visible(fn(callable $get) => !empty($get('theme_id')))
                                                    ->required()
                                                    ->allowHtml()
                                                    ->native(false)
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->fonts) && is_array($theme->fonts) && count($theme->fonts) > 0) {
                                                                $set('author_font_family', $theme->fonts[0]);
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false),

                                                Select::make('author_font_size')
                                                    ->label('Font Size')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->font_sizes) || !is_array($theme->font_sizes)) {
                                                            return [];
                                                        }

                                                        $sizeOptions = [];
                                                        foreach ($theme->font_sizes as $sizeData) {
                                                            if (isset($sizeData['size']) && isset($sizeData['name'])) {
                                                                $sizeOptions[$sizeData['size']] = $sizeData['name'] . ' (' . $sizeData['size'] . 'px)';
                                                            }
                                                        }

                                                        return $sizeOptions;
                                                    })
                                                    ->visible(fn(callable $get) => !empty($get('theme_id')))
                                                    ->required()
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->font_sizes) && is_array($theme->font_sizes) && count($theme->font_sizes) > 0) {
                                                                // For author font size, use a smaller size than content by default
                                                                if (count($theme->font_sizes) > 1 && isset($theme->font_sizes[1]['size'])) {
                                                                    $set('author_font_size', $theme->font_sizes[1]['size']);
                                                                } else if (isset($theme->font_sizes[0]['size'])) {
                                                                    $set('author_font_size', $theme->font_sizes[0]['size']);
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false),

                                                Select::make('author_font_color')
                                                    ->label('Font Color')
                                                    ->options(function (callable $get) {
                                                        $themeId = $get('theme_id');
                                                        if (!$themeId) {
                                                            return [];
                                                        }

                                                        $theme = Theme::find($themeId);
                                                        if (!$theme || empty($theme->colors) || !is_array($theme->colors)) {
                                                            return [];
                                                        }

                                                        $colorOptions = [];
                                                        foreach ($theme->colors as $colorData) {
                                                            if (isset($colorData['name']) && isset($colorData['value'])) {
                                                                $colorOptions[$colorData['value']] = '<div style="display: flex; align-items: center;"><span style="display: inline-block; width: 16px; height: 16px; background-color: ' . $colorData['value'] . '; border-radius: 4px; margin-right: 8px;"></span>' . $colorData['name'] . '</div>';
                                                            }
                                                        }

                                                        return $colorOptions;
                                                    })
                                                    ->visible(fn(callable $get) => !empty($get('theme_id')))
                                                    ->required()
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        if (empty($state) && !empty($get('theme_id'))) {
                                                            $theme = Theme::find($get('theme_id'));
                                                            if ($theme && !empty($theme->colors) && is_array($theme->colors) && count($theme->colors) > 0) {
                                                                if (isset($theme->colors[0]['value'])) {
                                                                    $set('author_font_color', $theme->colors[0]['value']);
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->selectablePlaceholder(false)
                                                    ->allowHtml(),
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

                Tables\Columns\TextColumn::make('background_type')
                    ->label('Background')
                    ->formatStateUsing(function (string $state, ImageTemplate $record): string {
                        if ($state === 'color') {
                            return "Color: <span style='display:inline-block; width:12px; height:12px; background-color:{$record->background_color}; border-radius:2px; margin-right:4px;'></span>";
                        } elseif ($state === 'image') {
                            return "<img src='" . ImageStore::url($record->background_image) . "' class='w-10 h-10 object-cover'>";
                        }
                        return ucfirst($state);
                    })->html(),

                Tables\Columns\TextColumn::make('content_font_family')
                    ->label('Content Settings')
                    ->formatStateUsing(function (ImageTemplate $record) {

                        $contentFontName = FontHelper::getRenderedFontName($record->content_font_family ?? '');

                        $html = '';

                        if ($contentFontName) {
                            $html .= "{$contentFontName}, {$record->content_font_size}px";
                            if ($record->content_font_color) {
                                $html .= " <span style='display:inline-block; width:12px; height:12px; background-color:{$record->content_font_color}; border-radius:2px; margin-right:4px;'></span>";
                            }
                            $html .= "<br>";
                        }

                        return $html ?: 'No text settings configured';
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('author_font_family')
                    ->label('Author Settings')
                    ->formatStateUsing(function (ImageTemplate $record) {

                        $authorFontName = FontHelper::getRenderedFontName($record->author_font_family ?? '');

                        $html = '';

                        if ($authorFontName) {
                            $html .= "{$authorFontName}, {$record->author_font_size}px";
                            if ($record->author_font_color) {
                                $html .= " <span style='display:inline-block; width:12px; height:12px; background-color:{$record->author_font_color}; border-radius:2px; margin-right:4px;'></span>";
                            }
                        }

                        return $html ?: 'No text settings configured';
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('theme.fonts')
                    ->label('Available Fonts')
                    ->formatStateUsing(function (ImageTemplate $record) {
                        if (!$record->theme || empty($record->theme->fonts)) {
                            return 'No fonts available';
                        }

                        $count = count($record->theme->fonts);
                        return "{$count} fonts available";
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('theme.colors')
                    ->label('Available Colors')
                    ->formatStateUsing(function (ImageTemplate $record) {
                        if (!$record->theme || empty($record->theme->colors)) {
                            return 'No colors available';
                        }

                        $count = count($record->theme->colors);
                        return "{$count} colors available";
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('LLL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('LLL')
                    ->sortable()
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
            'index' => Pages\ListImageTemplates::route('/'),
            'create' => Pages\CreateImageTemplate::route('/create'),
            'edit' => Pages\EditImageTemplate::route('/{record}/edit'),
        ];
    }
}
