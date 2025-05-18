<?php

namespace App\Filament\Company\Resources\PlatformPostResource\Pages;

use App\Filament\Company\Resources\PlatformPostResource;
use App\Filament\Company\Resources\PlatformPostResource\Actions\DeletePlatformPostAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformPost extends EditRecord
{
    protected static string $resource = PlatformPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeletePlatformPostAction::forEditPage()
                ->successRedirectUrl(PlatformPostResource::getUrl('index')),
        ];
    }
}
