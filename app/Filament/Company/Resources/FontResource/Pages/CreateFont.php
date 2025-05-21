<?php

namespace App\Filament\Company\Resources\FontResource\Pages;

use App\Filament\Company\Resources\FontResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateFont extends CreateRecord
{
    protected static string $resource = FontResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        $data['is_system'] = false;
        
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
