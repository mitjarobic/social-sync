<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-4">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold">Post Metrics</h3>
        <div class="text-xs text-gray-500">
            @if($getRecord()->metrics_updated_at)
                Updated {{ $getRecord()->metrics_updated_at->diffForHumans() }}
            @else
                No metrics data
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4">
        <x-filament::metrics-bar
            label="Reach"
            :value="$getRecord()->reach ?? 0"
            :max="1000"
            color="emerald"
        />

        <x-filament::metrics-bar
            label="Likes and Reactions"
            :value="$getRecord()->likes ?? 0"
            :max="100"
            color="blue"
        />

        <x-filament::metrics-bar
            label="Comments"
            :value="$getRecord()->comments ?? 0"
            :max="50"
            color="amber"
        />

        <x-filament::metrics-bar
            label="Shares"
            :value="$getRecord()->shares ?? 0"
            :max="20"
            color="purple"
        />
    </div>

    <div class="mt-4 text-right">
        <button
            type="button"
            class="text-xs text-primary-600 hover:text-primary-500"
            onclick="refreshMetrics({{ $getRecord()->id ?? 0 }})"
        >
            Refresh Metrics
        </button>
    </div>
</div>

<script>
    function refreshMetrics(id) {
        // In a real implementation, this would call an endpoint to refresh metrics
        // For now, we'll just reload the page
        window.location.reload();
    }
</script>
