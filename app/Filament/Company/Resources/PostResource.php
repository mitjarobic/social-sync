<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Post;
use Filament\Tables;
use App\Models\Platform;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\PostResource\Pages;
use App\Filament\Company\Resources\PostResource\RelationManagers;

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
                        \Filament\Forms\Components\Section::make('Widgets')
                            ->schema([
                                Forms\Components\TextInput::make('content')
                                    ->required()
                                    ->label('Post Content'),
                                Forms\Components\Repeater::make('platformPosts')
                                    ->relationship()
                                    ->schema([
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

                                                // Debug output
                                                debug($usedPlatformIds); // Laravel Debugbar
                                                logger()->debug('Used platform IDs:', $usedPlatformIds);

                                                return \App\Models\Platform::query()
                                                    ->whereNotIn('id', $usedPlatformIds)
                                                    ->pluck('name', 'id');
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
                                    ->options([
                                        'draft' => 'Draft',
                                        'scheduled' => 'Scheduled',
                                        'posted' => 'Posted',
                                    ])
                                    ->default('draft')
                                    ->label('Status'),
                            ])
                            ->columnSpan(1),

                        // Right Column (Custom View)
                        \Filament\Forms\Components\View::make('filament.custom.sidebar-view')
                            ->columnSpan(1),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')->limit(50),
                Tables\Columns\TextColumn::make('platform')->label('Social Media Platform'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('scheduled_at')->sortable(),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
