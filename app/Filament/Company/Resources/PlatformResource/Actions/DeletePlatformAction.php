<?php

namespace App\Filament\Company\Resources\PlatformResource\Actions;

use App\Models\Platform;
use App\Services\PlatformDeletionService;
use Filament\Actions\DeleteAction as HeaderDeleteAction;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Filament\Notifications\Notification;

class DeletePlatformAction
{
    /**
     * Get a delete action configured for table context
     */
    public static function forTable(): TableDeleteAction
    {
        return static::configureAction(TableDeleteAction::make());
    }

    /**
     * Get a delete action configured for edit page context
     */
    public static function forEditPage(): HeaderDeleteAction
    {
        return static::configureAction(HeaderDeleteAction::make());
    }

    /**
     * Configure the action with common settings
     *
     * @param TableDeleteAction|HeaderDeleteAction $action
     * @return TableDeleteAction|HeaderDeleteAction
     */
    protected static function configureAction($action)
    {
        return $action
            ->label('Remove')
            ->requiresConfirmation()
            ->modalHeading('Remove platform?')
            ->modalDescription('Are you sure you want to remove this platform? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, remove platform')
            ->action(function (Platform $record) {
                return static::handleDeletion($record);
            });
    }

    /**
     * Handle the actual deletion logic
     */
    protected static function handleDeletion(Platform $record): bool
    {
        // Use the dedicated service to handle deletion
        $deletionService = new PlatformDeletionService();
        $result = $deletionService->delete($record);

        // Show appropriate notification based on the result
        if ($result['success']) {
            Notification::make()
                ->success()
                ->title('Success')
                ->body($result['message'])
                ->send();

            return true;
        } else {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($result['message'])
                ->send();

            // Cancel the deletion process
            return false;
        }
    }
}
