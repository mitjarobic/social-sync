<?php

namespace App\Filament\Company\Resources\PlatformResource\Pages;

use Filament\Actions;
use App\Services\PlatformSyncService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Company\Resources\PlatformResource;

class ListPlatforms extends ListRecords
{
    protected static string $resource = PlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->refreshTable()),
        ];
    }

    protected function refreshTable()
    {
        (new PlatformSyncService(auth()->user()))->syncPlatforms();

          Notification::make()
                ->success()
                ->title('Success')
                ->body("Platforms refreshed successfully.")
                ->send();

    }
}
