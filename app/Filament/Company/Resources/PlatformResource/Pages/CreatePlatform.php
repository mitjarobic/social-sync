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
        $companyId = \Filament\Facades\Filament::getTenant()->id;
        $provider = $data['provider'];

        // Check if company already has a platform of this type
        $existingPlatform = Platform::where('company_id', $companyId)
            ->where('provider', $provider)
            ->first();

        if ($existingPlatform) {
            \Filament\Notifications\Notification::make()
                ->title('Platform Already Exists')
                ->body("Your company already has a {$provider} platform. Only one platform per type is allowed per company.")
                ->danger()
                ->send();

            $this->halt();
        }

        $model = Platform::where('external_id', $data['external_id'])
            ->where('provider', $provider)
            ->whereNull('company_id') // Only platforms without company_id can be claimed
            ->first();

        if (!$model) {
            \Filament\Notifications\Notification::make()
                ->title('Platform Not Available')
                ->body("The selected {$provider} platform is not available or has already been claimed by another company.")
                ->danger()
                ->send();

            $this->halt();
        }

        $model->update([
            'company_id' => $companyId,
            'label' => $data['label'],
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Platform Added Successfully')
            ->body("The {$provider} platform '{$data['label']}' has been added to your company.")
            ->success()
            ->send();

        return $model;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
