<?php

namespace App\Filament\Company\Resources\PostResource\Pages;

use App\Filament\Company\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->refreshTable() // 👈 this triggers the table reload
                ->action(fn () => $this->refreshTable()),
        ];
    }
}
