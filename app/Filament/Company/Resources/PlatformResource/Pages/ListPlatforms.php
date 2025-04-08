<?php

namespace App\Filament\Company\Resources\PlatformResource\Pages;

use App\Filament\Company\Resources\PlatformResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatforms extends ListRecords
{
    protected static string $resource = PlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
