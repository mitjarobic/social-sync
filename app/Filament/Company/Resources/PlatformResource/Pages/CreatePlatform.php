<?php

namespace App\Filament\Company\Resources\PlatformResource\Pages;

use Filament\Actions;
use App\Models\Platform;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Company\Resources\PlatformResource;

class CreatePlatform extends CreateRecord
{
    protected static string $resource = PlatformResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $model = Platform::where('external_id', $data['external_id'])
            ->where('provider', $data['provider'])
            ->firstOrFail();

        $model->update([
            'company_id' => auth()->user()->currentCompany->id,
            'label' => $data['label'],
        ]);

        return $model;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
