<?php

namespace App\Filament\Company\Resources\PlatformPostResource\Actions;

use App\Models\PlatformPost;
use App\Services\PlatformPostDeletionService;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

class DeletePlatformPostAction extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete')
            ->modalHeading('Delete platform post?')
            ->modalDescription('Are you sure you want to delete this post? This will remove it from the platform and cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete post')
            ->action(function (Model|PlatformPost $record) {
                // Use the dedicated service to handle deletion
                $deletionService = new PlatformPostDeletionService();
                $result = $deletionService->delete($record);

                // Show appropriate notification based on the result
                if ($result['success']) {
                    $this->success($result['message']);
                } else {
                    $this->failure($result['message']);
                }
            });
    }
}
