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
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
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
                                            ->options(PostStatus::class)
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
                                                    ->label('Scheduled At'),

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
                                                return Platform::count();
                                            })
                                            ->default(function () {
                                                return Platform::all()->map(
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
            ->columns([
                Tables\Columns\TextColumn::make('content')->limit(50),
                Tables\Columns\TextColumn::make('platformPosts.platform.name')
                    ->label('Platforms')
                    ->formatStateUsing(function (Post $record) {
                        return $record->platformPosts
                            ->pluck('platform.name')
                            ->unique()
                            ->join(', ');
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(PostStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('created_at')->sortable(),
                Tables\Columns\TextColumn::make('platform_status')
                    ->label('Platform Status')
                    ->formatStateUsing(function (Post $record) {
                        $statuses = $record->platformPosts
                            ->groupBy('status')
                            ->map(
                                fn($items, $status) =>
                                $items->count() . ' ' . Str::of($status)->title()
                            )
                            ->join(', ');

                        return $statuses ?: 'No platforms';
                    })
                    ->badge()
                    ->color(fn(Post $record) => match (true) {
                        $record->platformPosts->contains('status', 'failed') => 'danger',
                        $record->platformPosts->every('status', 'published') => 'success',
                        default => 'warning'
                    })
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->requiresConfirmation()
                    ->action(function (Post $record) {
                        $record->update([
                            'status' => \App\Enums\PostStatus::PUBLISHING
                        ]);

                        \App\Jobs\DispatchPlatformPosts::dispatch($record);
                    })
                    ->visible(
                        fn(Post $record) =>
                        $record->status === \App\Enums\PostStatus::SCHEDULED
                    )
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
