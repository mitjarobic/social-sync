<?php

namespace App\Filament\Company\Resources\PlatformPostResource\Actions;

use App\Jobs\UpdatePlatformPostMetrics;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class RefreshMetricsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'refreshMetrics';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Refresh Metrics')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->action(function (Model $record): void {
                // Dispatch the job to update metrics
                UpdatePlatformPostMetrics::dispatch($record);
                
                // Show a notification
                $this->success();
            })
            ->successNotificationTitle('Metrics refresh has been queued')
            ->requiresConfirmation()
            ->modalHeading('Refresh Metrics')
            ->modalDescription('Are you sure you want to refresh the metrics for this post? This will queue a background job to fetch the latest metrics from the platform.')
            ->modalSubmitActionLabel('Yes, refresh metrics');
    }
}
