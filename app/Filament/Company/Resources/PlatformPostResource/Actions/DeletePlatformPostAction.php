<?php

namespace App\Filament\Company\Resources\PlatformPostResource\Actions;

use App\Models\PlatformPost;
use App\Services\PlatformPostDeletionService;
use Filament\Actions\DeleteAction as HeaderDeleteAction;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Filament\Notifications\Notification;

class DeletePlatformPostAction
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
            ->requiresConfirmation()
            ->modalHeading('Delete platform post?')
            ->modalDescription('Are you sure you want to delete this post? This will remove it from the platform and cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete post')
            ->action(function (PlatformPost $record) {
                return static::handleDeletion($record);
            });
    }

    /**
     * Handle the actual deletion logic
     */
    protected static function handleDeletion(PlatformPost $record): bool
    {
        // Use the dedicated service to handle deletion
        $deletionService = new PlatformPostDeletionService();
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
