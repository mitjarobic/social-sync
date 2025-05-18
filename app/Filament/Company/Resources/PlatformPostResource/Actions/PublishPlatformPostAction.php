<?php

namespace App\Filament\Company\Resources\PlatformPostResource\Actions;

use App\Enums\PlatformPostStatus;
use App\Models\PlatformPost;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PublishPlatformPostAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'publish';
    }

    public function getLabel(): string
    {
        return 'Publish';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-paper-airplane';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->color('success')
            ->requiresConfirmation()
            ->modalHeading('Publish post')
            ->modalDescription('Are you sure you want to publish this post to the platform?')
            ->modalSubmitActionLabel('Yes, publish now')
            ->action(function (Model|PlatformPost $record): void {
                // Update status to publishing
                $record->update([
                    'status' => PlatformPostStatus::PUBLISHING,
                ]);

                // Dispatch the job to publish this specific platform post
                try {
                    // Dispatch the job to publish this specific platform post
                    \App\Jobs\PublishPlatformPost::dispatch($record);

                    $this->success('Post has been queued for publishing');
                } catch (\Exception $e) {
                    // If there's an error dispatching the job, update status to failed
                    $record->update([
                        'status' => PlatformPostStatus::FAILED,
                    ]);

                    Log::error('Failed to dispatch publish job for platform post', [
                        'platform_post_id' => $record->id,
                        'error' => $e->getMessage(),
                    ]);

                    $this->failure('Failed to queue post for publishing: ' . $e->getMessage());
                }
            });
    }
}
