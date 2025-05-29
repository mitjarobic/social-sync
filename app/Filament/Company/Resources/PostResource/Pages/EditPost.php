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

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save')
                ->requiresConfirmation(fn() => $this->form->getState()['status'] === 'draft')
                ->modalHeading('Keep status as draft?')
                ->modalDescription('Do you want to keep the status to "draft" before saving?')
                ->modalSubmitActionLabel('Yes')
                ->modalCancelActionLabel('No')
                ->action(function () {
                    $data = $this->form->getState();

                    $this->record->fill($data)->save();

                    \Filament\Notifications\Notification::make()
                        ->title('Saved')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
