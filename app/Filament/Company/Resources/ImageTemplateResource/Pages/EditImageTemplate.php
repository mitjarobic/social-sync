<?php

namespace App\Filament\Company\Resources\ImageTemplateResource\Pages;

use App\Filament\Company\Resources\ImageTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImageTemplate extends EditRecord
{
    protected static string $resource = ImageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
