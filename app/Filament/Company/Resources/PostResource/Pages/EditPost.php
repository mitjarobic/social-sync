<?php

namespace App\Filament\Company\Resources\PostResource\Pages;

use App\Filament\Company\Resources\PostResource;
use App\Filament\Company\Resources\PostResource\Actions\DeletePostAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeletePostAction::forEditPage()
                ->successRedirectUrl(PostResource::getUrl('index')),
        ];
    }
}
