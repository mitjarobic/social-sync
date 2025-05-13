@props([
    'value' => 0,
    'max' => 100,
    'label' => '',
    'color' => 'emerald',
])

<div class="flex flex-col space-y-1">
    <div class="flex justify-between items-center">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
        <span class="text-sm font-bold">{{ $value }}</span>
    </div>
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
        @php
            $percentage = min(100, max(0, ($value / $max) * 100));
            $width = $percentage . '%';
            
            $colorClasses = match($color) {
                'emerald' => 'bg-emerald-500',
                'blue' => 'bg-blue-500',
                'amber' => 'bg-amber-500',
                'purple' => 'bg-purple-500',
                default => 'bg-gray-500',
            };
        @endphp
        <div class="{{ $colorClasses }} h-2 rounded-full" style="width: {{ $width }}"></div>
    </div>
</div>
