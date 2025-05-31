<?php

namespace App\Filament\Company\Resources\Schemas;

use Filament\Forms;
use Filament\Tables;
use App\Support\ImageStore;
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

            Forms\Components\DateTimePicker::make('scheduled_at')
                ->native(false)
                ->timezone(\App\Support\TimezoneHelper::getUserTimezone())
                ->displayFormat('LLL'),

            Forms\Components\DateTimePicker::make('posted_at')
                ->native(false)
                ->timezone(\App\Support\TimezoneHelper::getUserTimezone())
                ->displayFormat('LLL'),
        ];
    }

    public static function table(): array
    {
        return [
            Tables\Columns\ImageColumn::make('post.imagePath')
                ->label('')
                ->size(30)
                ->getStateUsing(function ($record) {
                    return $record->post->image_path ? ImageStore::url($record->post->image_path) . '?v=' . $record->updated_at->timestamp : null;
                }),

            Tables\Columns\TextColumn::make('post.content')
                ->label('Caption')
                ->limit(40)
                ->tooltip(fn($record) => $record->post->content)
                ->searchable(),
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
            // Individual metrics columns
            Tables\Columns\TextColumn::make('reach')
                ->label('Reach')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->alignCenter()
                ->sortable(),

            Tables\Columns\TextColumn::make('likes')
                ->label('Likes')
                ->icon('heroicon-o-heart')
                ->color('primary')
                ->alignCenter()
                ->sortable(),

            Tables\Columns\TextColumn::make('comments')
                ->label('Comments')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->alignCenter()
                ->sortable(),

            Tables\Columns\TextColumn::make('shares')
                ->label('Shares')
                ->icon('heroicon-o-share')
                ->color('info')
                ->alignCenter()
                ->sortable()
                ->tooltip('Number of shares. Note: Instagram regular posts will always show 0 shares as Instagram does not provide share metrics for regular posts through their API (only for stories).'),
            Tables\Columns\TextColumn::make('external_url')
                ->label('URL')
                ->formatStateUsing(fn() => 'Link')
                ->url(fn($record) => $record->external_url)
                ->openUrlInNewTab()
                ->color('primary'),  // optional: makes it look like a link
            Tables\Columns\TextColumn::make('metrics_updated_at')
                ->label('Metrics Updated')
                ->formatStateUsing(function ($state) {
                    if (!$state) return null;
                    return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                })
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('scheduled_at')
                ->label('Scheduled At')
                ->formatStateUsing(function ($state) {
                    if (!$state) return null;
                    return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                })
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('posted_at')
                ->label('Published At')
                ->formatStateUsing(function ($state) {
                    if (!$state) return null;
                    return \App\Support\TimezoneHelper::formatInUserTimezone($state);
                })
                ->sortable(),
        ];
    }
}
