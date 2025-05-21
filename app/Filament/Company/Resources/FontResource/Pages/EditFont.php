<?php

namespace App\Filament\Company\Resources\FontResource\Pages;

use App\Filament\Company\Resources\FontResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFont extends EditRecord
{
    protected static string $resource = FontResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove font_type as it's not in the model
        if (isset($data['font_type'])) {
            unset($data['font_type']);
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
