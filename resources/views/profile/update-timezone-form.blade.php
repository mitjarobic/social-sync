<x-filament-companies::grid-section md="2">
    <x-slot name="title">
        Timezone
    </x-slot>

    <x-slot name="description">
        Update your account's timezone to ensure dates and times are displayed correctly.
    </x-slot>

    <x-filament::section>
        <x-filament-panels::form wire:submit="updateTimezone">
            {{ $this->form }}

            <div class="text-left mt-4">
                <x-filament::button type="submit">
                    {{ __('Save') }}
                </x-filament::button>
            </div>
        </x-filament-panels::form>
    </x-filament::section>
</x-filament-companies::grid-section>
