<?php

namespace App\Filament\Company\Resources\PlatformResource\Pages;

use App\Filament\Company\Resources\PlatformResource;
use App\Filament\Company\Resources\PlatformResource\Actions\DeletePlatformAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatform extends EditRecord
{
    protected static string $resource = PlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeletePlatformAction::forEditPage()
                ->successRedirectUrl(PlatformResource::getUrl('index')),
        ];
    }
}
