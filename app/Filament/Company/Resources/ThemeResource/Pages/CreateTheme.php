<?php

namespace App\Filament\Company\Resources\ThemeResource\Pages;

use App\Filament\Company\Resources\ThemeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTheme extends CreateRecord
{
    protected static string $resource = ThemeResource::class;
    
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
