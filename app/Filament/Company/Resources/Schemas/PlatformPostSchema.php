<?php

namespace App\Filament\Company\Resources\Schemas;

use Filament\Forms;
use Filament\Tables;
use App\Enums\PlatformPostStatus;

class PlatformPostSchema
{
    public static function form(): array
    {
        return [
            Forms\Components\Select::make('post_id')
                ->relationship('post', 'content')
                ->required(),

            Forms\Components\Select::make('platform_id')
                ->relationship('platform', 'label')
                ->required(),

            Forms\Components\Select::make('status')
                ->options(\App\Enums\PlatformPostStatus::values())
                ->required(),

            Forms\Components\TextInput::make('external_id')
                ->label('External ID'),

            Forms\Components\TextInput::make('external_url')
                ->label('External URL'),

            Forms\Components\Textarea::make('metadata'),

            // Metrics fields
            Forms\Components\Section::make('Metrics')
                ->schema([
                    Forms\Components\TextInput::make('reach')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('likes')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('comments')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('shares')
                        ->numeric()
                        ->default(0),

                    Forms\Components\DateTimePicker::make('metrics_updated_at'),
                ])
                ->columns(2),

            Forms\Components\DateTimePicker::make('scheduled_at'),

            Forms\Components\DateTimePicker::make('posted_at'),
        ];
    }

    public static function table(): array
    {
        return [
            Tables\Columns\TextColumn::make('post.content')->label('Post'),
            Tables\Columns\TextColumn::make('platform.label')->label('Platform'),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn(PlatformPostStatus $state): string => match ($state) {
                    'published' => 'success',
                    'publishing' => 'warning',
                    'queued' => 'info',
                    'failed' => 'danger',
                    default => 'gray',
                }),
            // Metrics column with custom component
            Tables\Columns\ViewColumn::make('metrics')
                ->label('Metrics')
                ->view('filament.tables.columns.platform-post-metrics')
                ->alignCenter(),
            Tables\Columns\TextColumn::make('external_url')
                ->label('URL')
                ->formatStateUsing(fn() => 'Link')
                ->url(fn($record) => $record->external_url)
                ->openUrlInNewTab()
                ->color('primary'),  // optional: makes it look like a link
            Tables\Columns\TextColumn::make('metrics_updated_at')
                ->label('Metrics Updated')
                ->dateTime()
                ->sortable(),

            Tables\Columns\TextColumn::make('scheduled_at')->dateTime(),
            Tables\Columns\TextColumn::make('posted_at')->dateTime(),
        ];
    }
}
