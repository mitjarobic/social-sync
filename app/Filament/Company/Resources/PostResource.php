<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Post;
use Filament\Tables;
use App\Models\Platform;
use Filament\Forms\Form;
use App\Enums\PostStatus;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Livewire\Attributes\Reactive;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\PostResource\Pages;
use App\Filament\Company\Resources\PostResource\RelationManagers;
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
                \Filament\Forms\Components\Grid::make(2)
                    ->schema([
                        // Left Column (Widgets)
                        \Filament\Forms\Components\Section::make('Post')
                            ->schema([
                                Forms\Components\Textarea::make('content')
                                    ->required()
                                    ->label('Content'),
                                Forms\Components\Repeater::make('platformPosts')
                                    ->relationship()
                                    ->default([
                                        ['status' => 'draft'] // Auto-set for new items
                                    ])
                                    ->schema([
                                        Forms\Components\TextInput::make('company_id')
                                            ->default(auth()->user()->currentCompany->id)
                                            ->dehydrated(true),
                                        Forms\Components\Select::make('platform_id') // platform_id
                                            ->options(function (callable $get, callable $set, $state, $livewire) {
                                                // Get all repeater items
                                                // Access parent repeater state
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
                                            ->live() // Add live() to ensure real-time updates

                                            ->afterStateUpdated(function (?string $state, ?string $old, callable $set, callable $get) {
                                                // Trigger Livewire update without resetting values
                                                // $set('../../refresh_key', now()->timestamp);
                                                // Debug to multiple channels
                                                debug($state, $get('../../postPlatforms')); // Laravel Debugbar
                                            }),
                                        Forms\Components\DateTimePicker::make('scheduled_at')
                                            ->label('Scheduled At')
                                            ->required(),
                                    ])
                                    ->label('Scheduled Platforms')
                                    ->columns(2)
                                    ->addActionLabel('Add Platform') // Correct for Filament v3
                                    ->maxItems(function () {
                                        return Platform::count(); // Limits to number of available platforms
                                    })
                                    ->default(function () {
                                        return Platform::all()->map(
                                            fn($platform) => ['platform_id' => $platform->id]
                                        )->toArray();
                                    }),
                                Forms\Components\Select::make('status')
                                    ->options(PostStatus::class)
                                    ->enum(PostStatus::class)
                                    ->default(PostStatus::DRAFT->value)
                                    ->required()
                                    ->label('Status'),
                                Forms\Components\View::make('platform_status')
                                    ->view('filament.components.posts.platform-status')
                                    ->viewData([
                                        'platformPosts' => function ($record) {
                                            return $record->platformPosts()->with('platform')->get();
                                        }
                                    ])
                                    ->columnSpanFull()
                                    ->visibleOn('edit') // Only show when editing
                            ])
                            ->columnSpan(1),
                        \Filament\Forms\Components\Section::make('Image')
                            ->schema([
                                Forms\Components\TextInput::make('image_content')
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($set) {
                                        $set('preview_version', now()->timestamp); // Force refresh
                                    })
                                    ->label('Content'),
                                Forms\Components\TextInput::make('image_author')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($set) {
                                        $set('preview_version', now()->timestamp);
                                    })
                                    ->label('Author'),
                                // Forms\Components\TextInput::make('preview_version')
                                //     ->hidden()
                                //     ->default(now()->timestamp),
                                Forms\Components\Placeholder::make('preview')
                                    ->label('Image Preview')
                                    ->content(function ($get) {
                                        return new HtmlString(
                                            view('filament.custom.social-preview', [
                                                'content' => $get('image_content'),
                                                'author' => $get('image_author'),
                                                'version' => 1,
                                            ])->render()
                                        );
                                    }),
                            ])
                            ->columnSpan(1),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')->limit(50),
                Tables\Columns\TextColumn::make('platformPosts.platform.name')
                    ->label('Platforms')
                    ->formatStateUsing(function ($state, Post $record) {
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
                        $record->status === \App\Enums\PostStatus::DRAFT
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
