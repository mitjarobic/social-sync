<x-filament-companies::grid-section md="2">
    <x-slot name="title">Facebook Connection</x-slot>
    <x-slot name="description">
        Connect your Facebook account to manage Pages and Insights.
    </x-slot>

    <x-filament::section>
        <div class="flex items-center justify-between">
            @if ($isFacebookConnected)
                <span class="text-green-500 font-medium">✅ Connected</span>
            @else
                <x-filament::button tag="a" color="warning" href="{{ route('facebook.redirect') }}">
                    Connect Facebook
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>
</x-filament-companies::grid-section>
