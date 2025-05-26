<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Post;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Platform;
use Filament\Forms\Form;
use App\Enums\PostStatus;
use Filament\Tables\Table;
use App\Support\ImageStore;
use App\Models\ImageTemplate;
use App\Helpers\FontHelper;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use App\Filament\Company\Resources\PostResource\Actions\DeletePostAction;
use App\Filament\Company\Resources\PostResource\Pages;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Company\Resources\PlatformPostRelationManagerResource\RelationManagers\PlatformPostsRelationManager;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Posts';

    protected static ?string $navigationGroup = 'Posting';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Two-column layout
                Forms\Components\Grid::make(2)
                    ->schema([
                        // Left column: Form fields

                        Forms\Components\Tabs::make('Post Settings')
                            ->tabs([
                                // Tab 1: Basic
                                Forms\Components\Tabs\Tab::make('Basic')
                                    ->schema([
                                        // Template Selection in Basic tab
                                        Forms\Components\Textarea::make('content')
                                            ->required()
                                            ->label('Content (Caption)')
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($set) {
                                                $set('preview_version', now()->timestamp);
                                            })
                                            ->rows(4)
                                            ->columnSpanFull(),

                                        // Image content and author moved to Content tab
                                        Forms\Components\Section::make('Image')
                                            ->schema([
                                                Forms\Components\Textarea::make('image_content')
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($set) {
                                                        $set('preview_version', now()->timestamp);
                                                    })
                                                    ->label('Content')
                                                    ->rows(3),

                                                Forms\Components\TextInput::make('image_author')
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($set) {
                                                        $set('preview_version', now()->timestamp);
                                                    })
                                                    ->label('Author'),
                                            ])
                                            ->columns(1),

                                        Forms\Components\Select::make('status')
                                            ->options(function () {
                                                // Only show DRAFT and SCHEDULED options
                                                $userSelectableStatuses = [
                                                    PostStatus::DRAFT,
                                                    PostStatus::SCHEDULED
                                                ];

                                                return collect($userSelectableStatuses)
                                                    ->mapWithKeys(fn(PostStatus $status) => [
                                                        $status->value => $status->label()
                                                    ])
                                                    ->toArray();
                                            })
                                            ->enum(PostStatus::class)
                                            ->default(PostStatus::DRAFT->value)
                                            ->required()
                                            ->label('Status')
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                // Update all platformPosts status based on post status
                                                $platformPostStatus = $state === PostStatus::SCHEDULED->value
                                                    ? 'queued'
                                                    : 'draft';

                                                // This will update the hidden status field in each platformPost
                                                $set('platformPosts.*.status', $platformPostStatus);
                                            }),

                                        Forms\Components\View::make('platform_status')
                                            ->view('filament.components.posts.platform-status')
                                            ->viewData([
                                                'platformPosts' => function ($record) {
                                                    return $record->platformPosts()->with('platform')->get();
                                                }
                                            ])
                                            ->columnSpanFull()
                                            ->visibleOn('edit'), // Only show when editing


                                    ]),

                                // Tab 2: Platforms
                                Forms\Components\Tabs\Tab::make('Platforms')
                                    ->schema([
                                        Forms\Components\Repeater::make('platformPosts')
                                            ->relationship()
                                            ->live()
                                            ->schema([
                                                Forms\Components\Hidden::make('company_id')
                                                    ->default(Auth::user()->currentCompany->id),
                                                Forms\Components\Select::make('platform_id')
                                                    ->options(function (callable $get, $state) {
                                                        // Get all repeater items
                                                        $rootState = $get('../');

                                                        // Get all platform_ids from all repeater items
                                                        $usedPlatformIds = collect($rootState)
                                                            ->pluck('platform_id')
                                                            ->filter() // Remove null values
                                                            ->reject(fn($id) => $id == $state) // Keep current selection
                                                            ->unique()
                                                            ->values()
                                                            ->toArray();

                                                        return \App\Models\Platform::query()
                                                            ->forCurrentCompany()
                                                            ->whereNotIn('id', $usedPlatformIds)
                                                            ->pluck('label', 'id');
                                                    })
                                                    ->reactive()
                                                    ->label('Platform')
                                                    ->live()
                                                    ->required()
                                                    ->afterStateUpdated(function (?string $state, callable $get) {
                                                        debug($state, $get('../../postPlatforms'));
                                                    }),
                                                Forms\Components\DateTimePicker::make('scheduled_at')
                                                    ->required()
                                                    ->label('Scheduled At')
                                                    ->native(true)
                                                    // ->minDate(now())
                                                    // ->maxDate(now()->addDays(30))
                                                    ->timezone(\App\Support\TimezoneHelper::getUserTimezone())
                                                    ->dehydrateStateUsing(function ($state) {
                                                        if (!$state) return null;
                                                        // Convert from user timezone to UTC for storage
                                                        return \App\Support\TimezoneHelper::toUTC($state);
                                                    })
                                                    ->seconds(false),


                                                // Forms\Components\TextInput::make('status')
                                                //     ->default(function (Get $get) {
                                                //         $postStatus = $get('../../status');
                                                //         return $postStatus === PostStatus::SCHEDULED->value
                                                //             ? 'queued'
                                                //             : 'draft';
                                                //     })
                                            ])
                                            ->label('')
                                            ->columns(2)
                                            ->addActionLabel('Add Platform')
                                            ->maxItems(function () {
                                                return Platform::forCurrentCompany()->count();
                                            })
                                            ->default(function () {
                                                return Platform::forCurrentCompany()->get()->map(
                                                    fn($platform) => ['platform_id' => $platform->id]
                                                )->toArray();
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                // Tab 3: Template
                                Forms\Components\Tabs\Tab::make('Design')
                                    ->schema([

                                        Forms\Components\Select::make('image_template_id')
                                            ->label('Select Image Template')
                                            ->options(function () {
                                                return ImageTemplate::where('company_id', Auth::user()->currentCompany->id)
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                if ($state) {
                                                    // When a template is selected, load its properties
                                                    $template = ImageTemplate::find($state);
                                                    if ($template) {
                                                        // Set use_custom_image_settings to false by default
                                                        $set('use_custom_image_settings', false);

                                                        // Load template properties into the form
                                                        if ($template->background_type === 'color') {
                                                            $set('image_bg_color', $template->background_color);
                                                            $set('image_bg_image_path', null);
                                                        } else {
                                                            $set('image_bg_image_path', $template->background_image);
                                                            $set('image_bg_color', '#000000'); // Default fallback
                                                        }

                                                        // Default font settings
                                                        $set('image_font', $template->font_family);
                                                        $set('image_font_size', $template->font_size);
                                                        $set('image_font_color', $template->font_color);

                                                        // Content-specific font settings
                                                        $set('content_font', $template->content_font_family ?? $template->font_family);
                                                        $set('content_font_size', $template->content_font_size ?? $template->font_size);
                                                        $set('content_font_color', $template->content_font_color ?? $template->font_color);

                                                        // Author-specific font settings
                                                        $set('author_font', $template->author_font_family ?? $template->font_family);
                                                        $set('author_font_size', $template->author_font_size ?? ($template->font_size ? intval($template->font_size * 0.7) : 78));
                                                        $set('author_font_color', $template->author_font_color ?? $template->font_color);

                                                        // Update image options
                                                        $set('image_options', [
                                                            'textAlignment' => $template->text_alignment,
                                                            'textPosition' => $template->text_position,
                                                            'padding' => $template->padding,
                                                        ]);

                                                        // Update preview
                                                        $set('preview_version', now()->timestamp);
                                                    }
                                                }
                                            }),
                                            
                                        // Hidden field for preview version
                                        Forms\Components\TextInput::make('preview_version')
                                            ->hidden()
                                            ->default(now()->timestamp),

                                        Forms\Components\Toggle::make('use_custom_image_settings')
                                            ->label('Customize Image Settings')
                                            ->helperText('Enable to override template settings with custom values')
                                            ->default(false)
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                // If toggle is turned off, reload template settings
                                                if (!$state) {
                                                    $templateId = $get('image_template_id');
                                                    if ($templateId) {
                                                        $template = ImageTemplate::find($templateId);
                                                        if ($template) {
                                                            // Load template properties into the form
                                                            if ($template->background_type === 'color') {
                                                                $set('image_bg_color', $template->background_color);
                                                                $set('image_bg_image_path', null);
                                                            } else {
                                                                $set('image_bg_image_path', $template->background_image);
                                                                $set('image_bg_color', '#000000'); // Default fallback
                                                            }

                                                            // Default font settings
                                                            $set('image_font', $template->font_family);
                                                            $set('image_font_size', $template->font_size);
                                                            $set('image_font_color', $template->font_color);

                                                            // Content-specific font settings
                                                            $set('content_font', $template->content_font_family ?? $template->font_family);
                                                            $set('content_font_size', $template->content_font_size ?? $template->font_size);
                                                            $set('content_font_color', $template->content_font_color ?? $template->font_color);

                                                            // Author-specific font settings
                                                            $set('author_font', $template->author_font_family ?? $template->font_family);
                                                            $set('author_font_size', $template->author_font_size ?? ($template->font_size ? intval($template->font_size * 0.7) : 78));
                                                            $set('author_font_color', $template->author_font_color ?? $template->font_color);

                                                            // Update image options
                                                            $set('image_options', [
                                                                'textAlignment' => $template->text_alignment,
                                                                'textPosition' => $template->text_position,
                                                                'padding' => $template->padding,
                                                            ]);
                                                        }
                                                    }
                                                }

                                                $set('preview_version', now()->timestamp);
                                            }),

                                        // Custom Settings (only visible when use_custom_image_settings is true)
                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                // Content Text Settings
                                                Forms\Components\Section::make('Content Text Settings')
                                                    ->schema([
                                                        Forms\Components\Select::make('content_font')
                                                            ->label('Content Font')
                                                            ->options(FontHelper::getStyledFontOptions())
                                                            ->allowHtml()
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(function ($set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),

                                                        Forms\Components\Select::make('content_font_size')
                                                            ->label('Content Font Size')
                                                            ->options(function (Get $get) {
                                                                $templateId = $get('image_template_id');
                                                                if (!$templateId) {
                                                                    return [
                                                                        '48' => 'Small (48px)',
                                                                        '64' => 'Medium (64px)',
                                                                        '80' => 'Large (80px)',
                                                                        '96' => 'Extra Large (96px)',
                                                                        '112' => 'Huge (112px)',
                                                                        '140' => 'Giant (140px)',
                                                                    ];
                                                                }

                                                                $template = \App\Models\ImageTemplate::find($templateId);
                                                                if (!$template || !$template->theme) {
                                                                    return [
                                                                        '48' => 'Small (48px)',
                                                                        '64' => 'Medium (64px)',
                                                                        '80' => 'Large (80px)',
                                                                        '96' => 'Extra Large (96px)',
                                                                        '112' => 'Huge (112px)',
                                                                        '140' => 'Giant (140px)',
                                                                    ];
                                                                }

                                                                $theme = $template->theme;
                                                                if (empty($theme->font_sizes)) {
                                                                    return [
                                                                        '48' => 'Small (48px)',
                                                                        '64' => 'Medium (64px)',
                                                                        '80' => 'Large (80px)',
                                                                        '96' => 'Extra Large (96px)',
                                                                        '112' => 'Huge (112px)',
                                                                        '140' => 'Giant (140px)',
                                                                    ];
                                                                }

                                                                $sizeOptions = [];
                                                                foreach ($theme->font_sizes as $fontSizeData) {
                                                                    if (isset($fontSizeData['size']) && isset($fontSizeData['name'])) {
                                                                        $sizeOptions[$fontSizeData['size']] = $fontSizeData['name'] . ' (' . $fontSizeData['size'] . 'px)';
                                                                    }
                                                                }

                                                                return $sizeOptions;
                                                            })
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(function ($set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),

                                                        Forms\Components\Select::make('content_font_color')
                                                            ->label('Content Font Color')
                                                            ->options(function (Get $get) {
                                                                $templateId = $get('image_template_id');
                                                                if (!$templateId) {
                                                                    return [
                                                                        '#ffffff' => 'White',
                                                                        '#000000' => 'Black',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $template = \App\Models\ImageTemplate::find($templateId);
                                                                if (!$template || !$template->theme) {
                                                                    return [
                                                                        '#ffffff' => 'White',
                                                                        '#000000' => 'Black',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $theme = $template->theme;
                                                                if (empty($theme->colors)) {
                                                                    return [
                                                                        '#ffffff' => 'White',
                                                                        '#000000' => 'Black',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $colorOptions = [];
                                                                foreach ($theme->colors as $colorData) {
                                                                    if (isset($colorData['name']) && isset($colorData['value'])) {
                                                                        $colorOptions[$colorData['value']] = $colorData['name'];
                                                                    }
                                                                }

                                                                return $colorOptions;
                                                            })
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(function ($set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),
                                                    ])
                                                    ->columns(3)
                                                    ->collapsed(true),

                                                // Author Text Settings
                                                Forms\Components\Section::make('Author Text Settings')
                                                    ->schema([
                                                        Forms\Components\Select::make('author_font')
                                                            ->label('Author Font')
                                                            ->options(FontHelper::getStyledFontOptions())
                                                            ->allowHtml()
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(function ($set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),

                                                        Forms\Components\Select::make('author_font_size')
                                                            ->label('Author Font Size')
                                                            ->options(function (Get $get) {
                                                                $templateId = $get('image_template_id');
                                                                if (!$templateId) {
                                                                    return [
                                                                        '48' => 'Small (48px)',
                                                                        '64' => 'Medium (64px)',
                                                                        '80' => 'Large (80px)',
                                                                        '96' => 'Extra Large (96px)',
                                                                        '112' => 'Huge (112px)',
                                                                        '140' => 'Giant (140px)',
                                                                    ];
                                                                }

                                                                $template = \App\Models\ImageTemplate::find($templateId);
                                                                if (!$template || !$template->theme) {
                                                                    return [
                                                                        '48' => 'Small (48px)',
                                                                        '64' => 'Medium (64px)',
                                                                        '80' => 'Large (80px)',
                                                                        '96' => 'Extra Large (96px)',
                                                                        '112' => 'Huge (112px)',
                                                                        '140' => 'Giant (140px)',
                                                                    ];
                                                                }

                                                                $theme = $template->theme;
                                                                if (empty($theme->font_sizes)) {
                                                                    return [
                                                                        '48' => 'Small (48px)',
                                                                        '64' => 'Medium (64px)',
                                                                        '80' => 'Large (80px)',
                                                                        '96' => 'Extra Large (96px)',
                                                                        '112' => 'Huge (112px)',
                                                                        '140' => 'Giant (140px)',
                                                                    ];
                                                                }

                                                                $sizeOptions = [];
                                                                foreach ($theme->font_sizes as $fontSizeData) {
                                                                    if (isset($fontSizeData['size']) && isset($fontSizeData['name'])) {
                                                                        $sizeOptions[$fontSizeData['size']] = $fontSizeData['name'] . ' (' . $fontSizeData['size'] . 'px)';
                                                                    }
                                                                }

                                                                return $sizeOptions;
                                                            })
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(function ($set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),

                                                        Forms\Components\Select::make('author_font_color')
                                                            ->label('Author Font Color')
                                                            ->options(function (Get $get) {
                                                                $templateId = $get('image_template_id');
                                                                if (!$templateId) {
                                                                    return [
                                                                        '#ffffff' => 'White',
                                                                        '#000000' => 'Black',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $template = \App\Models\ImageTemplate::find($templateId);
                                                                if (!$template || !$template->theme) {
                                                                    return [
                                                                        '#ffffff' => 'White',
                                                                        '#000000' => 'Black',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $theme = $template->theme;
                                                                if (empty($theme->colors)) {
                                                                    return [
                                                                        '#ffffff' => 'White',
                                                                        '#000000' => 'Black',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $colorOptions = [];
                                                                foreach ($theme->colors as $colorData) {
                                                                    if (isset($colorData['name']) && isset($colorData['value'])) {
                                                                        $colorOptions[$colorData['value']] = $colorData['name'];
                                                                    }
                                                                }

                                                                return $colorOptions;
                                                            })
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(function ($set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),
                                                    ])
                                                    ->columns(3)
                                                    ->collapsed(true),

                                                // Background Settings
                                                Forms\Components\Section::make('Background Settings')
                                                    ->schema([
                                                        Forms\Components\Select::make('image_bg_color')
                                                            ->label('Background Color')
                                                            ->options(function (Get $get) {
                                                                $templateId = $get('image_template_id');
                                                                if (!$templateId) {
                                                                    return [
                                                                        '#000000' => 'Black',
                                                                        '#ffffff' => 'White',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $template = \App\Models\ImageTemplate::find($templateId);
                                                                if (!$template || !$template->theme) {
                                                                    return [
                                                                        '#000000' => 'Black',
                                                                        '#ffffff' => 'White',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $theme = $template->theme;
                                                                if (empty($theme->colors)) {
                                                                    return [
                                                                        '#000000' => 'Black',
                                                                        '#ffffff' => 'White',
                                                                        '#0f172a' => 'Slate 900',
                                                                        '#ef4444' => 'Red 500',
                                                                        '#22c55e' => 'Green 500',
                                                                        '#3b82f6' => 'Blue 500',
                                                                        '#eab308' => 'Yellow 500',
                                                                        '#a855f7' => 'Purple 500',
                                                                        '#ec4899' => 'Pink 500',
                                                                        '#14b8a6' => 'Teal 500',
                                                                    ];
                                                                }

                                                                $colorOptions = [];
                                                                foreach ($theme->colors as $colorData) {
                                                                    if (isset($colorData['name']) && isset($colorData['value'])) {
                                                                        $colorOptions[$colorData['value']] = $colorData['name'];
                                                                    }
                                                                }

                                                                return $colorOptions;
                                                            })
                                                            ->default('#000000')
                                                            ->live(debounce: 500)
                                                            ->afterStateUpdated(function ($set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),

                                                        Forms\Components\FileUpload::make('image_bg_image_path')
                                                            ->label('Background Image')
                                                            ->directory('backgrounds')
                                                            ->image()
                                                            ->imageResizeMode('cover')
                                                            ->imageCropAspectRatio('1:1')
                                                            ->imageResizeTargetWidth('1080')
                                                            ->imageResizeTargetHeight('1080')
                                                            ->disk('public')
                                                            ->visibility('public')
                                                            ->maxFiles(1)
                                                            ->downloadable()
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
                                                            ->live(debounce: 500)
                                                            ->helperText('Upload a background image (1080x1080 recommended)')
                                                            ->afterStateUpdated(function (Set $set) {
                                                                $set('preview_version', now()->timestamp);
                                                            }),
                                                    ])
                                                    ->columns(1)
                                                    ->collapsed(false),
                                            ])
                                            ->visible(fn(Get $get) => $get('use_custom_image_settings')),
                                    ]),
                            ])
                            ->columnSpan(1),



                        Forms\Components\Tabs::make('Platform Previews')
                            ->tabs([
                                // Facebook Preview Tab
                                Forms\Components\Tabs\Tab::make('Facebook')
                                    ->schema([
                                        Forms\Components\Placeholder::make('')
                                            ->live()
                                            ->content(function (Get $get) {
                                                $bgImagePath = $get('image_bg_image_path');

                                                // If it's an array (from FileUpload), get the first element
                                                if (is_array($bgImagePath) && !empty($bgImagePath)) {
                                                    $bgImagePath = reset($bgImagePath);
                                                    $bgImagePath = $bgImagePath instanceof TemporaryUploadedFile ? $bgImagePath->getPathName() : ImageStore::path($bgImagePath);
                                                } else {
                                                    $bgImagePath = null;
                                                }

                                                // Get the active Facebook platform for this company
                                                $facebookPlatform = \App\Models\Platform::query()
                                                    ->forCurrentCompany()
                                                    ->where('provider', 'facebook')
                                                    ->first();

                                                return new HtmlString(
                                                    view(
                                                        'filament.custom.platform-previews.facebook-preview',
                                                        [
                                                            'content' => $get('content'),
                                                            'imageContent' => $get('image_content'),
                                                            'author' => $get('image_author'),
                                                            'contentFont' => $get('content_font'),
                                                            'contentFontSize' => $get('content_font_size'),
                                                            'contentFontColor' => $get('content_font_color'),
                                                            'authorFont' => $get('author_font'),
                                                            'authorFontSize' => $get('author_font_size'),
                                                            'authorFontColor' => $get('author_font_color'),
                                                            'bgColor' => $get('image_bg_color'),
                                                            'bgImagePath' => $bgImagePath,
                                                            'version' => $get('preview_version'),
                                                            'platform' => $facebookPlatform,
                                                        ]
                                                    )->render()
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                // Instagram Preview Tab
                                Forms\Components\Tabs\Tab::make('Instagram')
                                    ->schema([
                                        Forms\Components\Placeholder::make('')
                                            ->live()
                                            ->content(function (Get $get) {
                                                $bgImagePath = $get('image_bg_image_path');

                                                // If it's an array (from FileUpload), get the first element
                                                if (is_array($bgImagePath) && !empty($bgImagePath)) {
                                                    $bgImagePath = reset($bgImagePath);
                                                    $bgImagePath = $bgImagePath instanceof TemporaryUploadedFile ? $bgImagePath->getPathName() : ImageStore::path($bgImagePath);
                                                } else {
                                                    $bgImagePath = null;
                                                }

                                                // Get the active Instagram platform for this company
                                                $instagramPlatform = \App\Models\Platform::query()
                                                    ->forCurrentCompany()
                                                    ->where('provider', 'instagram')
                                                    ->first();

                                                return new HtmlString(
                                                    view('filament.custom.platform-previews.instagram-preview', [
                                                        'content' => $get('content'),
                                                        'imageContent' => $get('image_content'),
                                                        'author' => $get('image_author'),
                                                        'contentFont' => $get('content_font'),
                                                        'contentFontSize' => $get('content_font_size'),
                                                        'contentFontColor' => $get('content_font_color'),
                                                        'authorFont' => $get('author_font'),
                                                        'authorFontSize' => $get('author_font_size'),
                                                        'authorFontColor' => $get('author_font_color'),
                                                        'bgColor' => $get('image_bg_color'),
                                                        'bgImagePath' => $bgImagePath,
                                                        'version' => $get('preview_version'),
                                                        'platform' => $instagramPlatform,
                                                    ])->render()
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                // X Preview Tab
                                Forms\Components\Tabs\Tab::make('X')
                                    ->schema([
                                        Forms\Components\Placeholder::make('')
                                            ->live()
                                            ->content(function (Get $get) {
                                                $bgImagePath = $get('image_bg_image_path');

                                                // If it's an array (from FileUpload), get the first element
                                                if (is_array($bgImagePath) && !empty($bgImagePath)) {
                                                    $bgImagePath = reset($bgImagePath);
                                                    $bgImagePath = $bgImagePath instanceof TemporaryUploadedFile ? $bgImagePath->getPathName() : ImageStore::path($bgImagePath);
                                                } else {
                                                    $bgImagePath = null;
                                                }

                                                // Get the active X platform for this company
                                                $xPlatform = \App\Models\Platform::query()
                                                    ->forCurrentCompany()
                                                    ->where('provider', 'x')
                                                    ->first();


                                                return new HtmlString(
                                                    view('filament.custom.platform-previews.x-preview', [
                                                        'content' => $get('content'),
                                                        'imageContent' => $get('image_content'),
                                                        'author' => $get('image_author'),
                                                        'contentFont' => $get('content_font'),
                                                        'contentFontSize' => $get('content_font_size'),
                                                        'contentFontColor' => $get('content_font_color'),
                                                        'authorFont' => $get('author_font'),
                                                        'authorFontSize' => $get('author_font_size'),
                                                        'authorFontColor' => $get('author_font_color'),
                                                        'bgColor' => $get('image_bg_color'),
                                                        'bgImagePath' => $bgImagePath,
                                                        'version' => $get('preview_version'),
                                                        'platform' => $xPlatform,
                                                    ])->render()
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(1),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('')
                    ->size(30)
                    ->getStateUsing(function ($record) {
                        return $record->image_path ? ImageStore::url($record->image_path) : null;
                    }),

                Tables\Columns\TextColumn::make('content')
                    ->label('Caption')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('image_content')
                    ->label('Image Text')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('image_author')
                    ->label('Image Author')
                    ->limit(50)
                    ->searchable(),



                // Platforms column with icons - using Filament's native components
                Tables\Columns\TextColumn::make('platformPosts')
                    ->label('Platforms')
                    ->state(function (Post $record): array {
                        $platformPosts = $record->platformPosts()->with('platform')->get();

                        if ($platformPosts->isEmpty()) {
                            return ['None'];
                        }

                        $items = [];

                        foreach ($platformPosts as $platformPost) {
                            $platform = $platformPost->platform;
                            if (!$platform) continue;

                            $statusValue = $platformPost->status->value;
                            $statusLabel = ucfirst($statusValue);

                            $items[] = $platform->label . ' - ' . $statusLabel;
                        }

                        return $items;
                    })
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_contains($state, 'Published') => 'success',
                        str_contains($state, 'Publishing') => 'warning',
                        str_contains($state, 'Queued') => 'info',
                        str_contains($state, 'Failed') => 'danger',
                        default => 'gray',
                    })
                    ->separator(',')
                    ->listWithLineBreaks(),

                // Post status
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(PostStatus $state) => $state->color()),

                // Created at
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;
                        return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                    })
                    ->sortable(),

                // // Platform status with detailed information - using Filament's native components
                // Tables\Columns\TextColumn::make('platform_status')
                //     ->label('Platform Status')
                //     ->state(function (Post $record): array {
                //         // Reload the relationship to ensure we have the latest data
                //         $platformPosts = $record->platformPosts()->get();

                //         if ($platformPosts->isEmpty()) {
                //             return ['No platforms'];
                //         }

                //         // Group by status
                //         $groupedByStatus = $platformPosts->groupBy(function ($platformPost) {
                //             return $platformPost->status->value;
                //         });

                //         // Define the order we want statuses to appear
                //         $statusOrder = [
                //             \App\Enums\PlatformPostStatus::PUBLISHED->value,
                //             \App\Enums\PlatformPostStatus::PUBLISHING->value,
                //             \App\Enums\PlatformPostStatus::QUEUED->value,
                //             \App\Enums\PlatformPostStatus::FAILED->value,
                //             \App\Enums\PlatformPostStatus::DRAFT->value,
                //         ];

                //         $items = [];

                //         // Sort and format the statuses
                //         foreach ($statusOrder as $status) {
                //             if (!$groupedByStatus->has($status)) {
                //                 continue;
                //             }

                //             $count = $groupedByStatus->get($status)->count();
                //             $label = ucfirst($status);

                //             $items[] = "{$count} {$label}";
                //         }

                //         return $items;
                //     })
                //     ->badge()
                //     ->color(fn (string $state): string => match (true) {
                //         str_contains($state, 'Published') => 'success',
                //         str_contains($state, 'Publishing') => 'warning',
                //         str_contains($state, 'Queued') => 'info',
                //         str_contains($state, 'Failed') => 'danger',
                //         default => 'gray',
                //     })
                //     ->separator(' ')
                //     ->listWithLineBreaks()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    DeletePostAction::forTable(),
                    Tables\Actions\Action::make('publish')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Publish post')
                        ->modalDescription('Are you sure you want to publish this post to all platforms?')
                        ->modalSubmitActionLabel('Yes, publish now')
                        ->action(function (Post $record) {
                            $record->update([
                                'status' => \App\Enums\PostStatus::PUBLISHING
                            ]);

                            \App\Jobs\PublishPlatformPosts::dispatch($record);
                        })
                        ->visible(
                            fn(Post $record) =>
                            // Only show for scheduled posts that aren't already published or publishing
                            $record->status === \App\Enums\PostStatus::SCHEDULED &&
                                !$record->platformPosts()->where('status', \App\Enums\PlatformPostStatus::PUBLISHED)->exists() &&
                                !$record->platformPosts()->where('status', \App\Enums\PlatformPostStatus::PUBLISHING)->exists()
                        )
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
            PlatformPostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
