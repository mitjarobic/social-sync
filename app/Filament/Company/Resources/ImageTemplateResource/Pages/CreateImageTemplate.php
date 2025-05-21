<?php

namespace App\Filament\Company\Resources\ImageTemplateResource\Pages;

use App\Filament\Company\Resources\ImageTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateImageTemplate extends CreateRecord
{
    protected static string $resource = ImageTemplateResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
