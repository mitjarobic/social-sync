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
                                // Tab 1: Content
                                Forms\Components\Tabs\Tab::make('Content')
                                    ->schema([
                                        // Hidden field for preview version

                                        Forms\Components\Textarea::make('content')
                                            ->required()
                                            ->label('Post Content')
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($set) {
                                                $set('preview_version', now()->timestamp);
                                            })
                                            ->columnSpanFull(),

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
                                            ->visibleOn('edit') // Only show when editing
                                    ]),

                                // Tab 2: Platforms
                                Forms\Components\Tabs\Tab::make('Platforms')
                                    ->schema([
                                        Forms\Components\Repeater::make('platformPosts')
                                            ->relationship()
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
                                                    ->native(false)
                                                    ->timezone(\App\Support\TimezoneHelper::getUserTimezone())
                                                    ->displayFormat('LLL'),

                                                Forms\Components\TextInput::make('status')
                                                    ->default(function (Get $get) {
                                                        $postStatus = $get('../../status');
                                                        return $postStatus === PostStatus::SCHEDULED->value
                                                            ? 'queued'
                                                            : 'draft';
                                                    })
                                            ])
                                            ->label('Scheduled Platforms')
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

                                // Tab 3: Image Settings
                                Forms\Components\Tabs\Tab::make('Image Settings')
                                    ->schema([
                                        // Hidden field for preview version
                                        Forms\Components\TextInput::make('preview_version')
                                            ->hidden()
                                            ->default(now()->timestamp),

                                        // Content and Author
                                        Forms\Components\Section::make('Text Content')
                                            ->schema([
                                                Forms\Components\TextInput::make('image_content')
                                                    ->required()
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($set) {
                                                        $set('preview_version', now()->timestamp);
                                                    })
                                                    ->label('Content'),

                                                Forms\Components\TextInput::make('image_author')
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($set) {
                                                        $set('preview_version', now()->timestamp);
                                                    })
                                                    ->label('Author'),
                                            ])
                                            ->columns(1)
                                            ->collapsed(false),

                                        // Font Settings
                                        Forms\Components\Section::make('Font Settings')
                                            ->schema([
                                                Forms\Components\Select::make('image_font')
                                                    ->label('Font')
                                                    ->options([
                                                        'sansSerif.ttf' => 'Sans Serif',
                                                        'roboto.ttf' => 'Roboto',
                                                    ])
                                                    ->default('sansSerif.ttf')
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($set) {
                                                        $set('preview_version', now()->timestamp);
                                                    }),

                                                Forms\Components\TextInput::make('image_font_size')
                                                    ->label('Font Size')
                                                    ->type('number')
                                                    ->default(112)
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($set) {
                                                        $set('preview_version', now()->timestamp);
                                                    }),

                                                Forms\Components\ColorPicker::make('image_font_color')
                                                    ->label('Font Color')
                                                    ->default('#FFFFFF')
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($set) {
                                                        $set('preview_version', now()->timestamp);
                                                    }),
                                            ])
                                            ->columns(3)
                                            ->collapsed(false),

                                        // Background Settings
                                        Forms\Components\Section::make('Background Settings')
                                            ->schema([
                                                Forms\Components\ColorPicker::make('image_bg_color')
                                                    ->label('Background Color')
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
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                                                    ->live(debounce: 500)
                                                    ->helperText('Upload a background image (1080x1080 recommended)')
                                                    ->afterStateUpdated(function (Set $set) {
                                                        $set('preview_version', now()->timestamp);
                                                    }),
                                            ])
                                            ->columns(1)
                                            ->collapsed(false),
                                    ]),
                            ])
                            ->columnSpan(1),



                        Forms\Components\Tabs::make('Platform Previews')
                            ->tabs([
                                // Facebook Preview Tab
                                Forms\Components\Tabs\Tab::make('Facebook')
                                    ->schema([
                                        Forms\Components\Placeholder::make('facebook_preview')
                                            ->content(function (Get $get) {
                                                $bgImagePath = $get('image_bg_image_path');

                                                // If it's an array (from FileUpload), get the first element
                                                if (is_array($bgImagePath) && !empty($bgImagePath)) {
                                                    $bgImagePath = reset($bgImagePath);
                                                    $bgImagePath = $bgImagePath instanceof TemporaryUploadedFile ? $bgImagePath->getPathName() : ImageStore::path($bgImagePath);
                                                } else {
                                                    $bgImagePath = null;
                                                }

                                                return new HtmlString(
                                                    view(
                                                        'filament.custom.platform-previews.facebook-preview',
                                                        [
                                                            'content' => $get('content'),
                                                            'imageContent' => $get('image_content'),
                                                            'author' => $get('image_author'),
                                                            'font' => $get('image_font'),
                                                            'fontSize' => $get('image_font_size'),
                                                            'fontColor' => $get('image_font_color'),
                                                            'bgColor' => $get('image_bg_color'),
                                                            'bgImagePath' => $bgImagePath,
                                                            'version' => $get('preview_version'),
                                                        ]
                                                    )->render()
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                // Instagram Preview Tab
                                Forms\Components\Tabs\Tab::make('Instagram')
                                    ->schema([
                                        Forms\Components\Placeholder::make('instagram_preview')
                                            ->content(function (Get $get) {
                                                $bgImagePath = $get('image_bg_image_path');

                                                // If it's an array (from FileUpload), get the first element
                                                if (is_array($bgImagePath) && !empty($bgImagePath)) {
                                                    $bgImagePath = reset($bgImagePath);
                                                    $bgImagePath = $bgImagePath instanceof TemporaryUploadedFile ? $bgImagePath->getPathName() : ImageStore::path($bgImagePath);
                                                } else {
                                                    $bgImagePath = null;
                                                }

                                                return new HtmlString(
                                                    view('filament.custom.platform-previews.instagram-preview', [
                                                        'content' => $get('content'),
                                                        'imageContent' => $get('image_content'),
                                                        'author' => $get('image_author'),
                                                        'font' => $get('image_font'),
                                                        'fontSize' => $get('image_font_size'),
                                                        'fontColor' => $get('image_font_color'),
                                                        'bgColor' => $get('image_bg_color'),
                                                        'bgImagePath' => $bgImagePath,
                                                        'version' => $get('preview_version'),
                                                    ])->render()
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                // X Preview Tab
                                Forms\Components\Tabs\Tab::make('X')
                                    ->schema([
                                        Forms\Components\Placeholder::make('x_preview')
                                            ->content(function (Get $get) {
                                                $bgImagePath = $get('image_bg_image_path');

                                                // If it's an array (from FileUpload), get the first element
                                                if (is_array($bgImagePath) && !empty($bgImagePath)) {
                                                    $bgImagePath = reset($bgImagePath);
                                                    $bgImagePath = $bgImagePath instanceof TemporaryUploadedFile ? $bgImagePath->getPathName() : ImageStore::path($bgImagePath);
                                                } else {
                                                    $bgImagePath = null;
                                                }

                                                return new HtmlString(
                                                    view('filament.custom.platform-previews.x-preview', [
                                                        'content' => $get('content'),
                                                        'imageContent' => $get('image_content'),
                                                        'author' => $get('image_author'),
                                                        'font' => $get('image_font'),
                                                        'fontSize' => $get('image_font_size'),
                                                        'fontColor' => $get('image_font_color'),
                                                        'bgColor' => $get('image_bg_color'),
                                                        'bgImagePath' => $bgImagePath,
                                                        'version' => $get('preview_version'),
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
