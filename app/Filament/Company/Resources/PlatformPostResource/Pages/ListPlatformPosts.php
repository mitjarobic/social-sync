<?php

namespace App\Filament\Company\Resources\PlatformPostResource\Pages;

use App\Filament\Company\Resources\PlatformPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListPlatformPosts extends ListRecords
{
    protected static string $resource = PlatformPostResource::class;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
