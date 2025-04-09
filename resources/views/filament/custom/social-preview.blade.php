<div wire:key="preview-{{ $version }}">
    @if($content)
        <img 
            src="/generate-image?content={{ urlencode($content) }}&author={{ urlencode($author) }}&v={{ $version }}"
            class="w-full border aspect-square bg-black"
            wire:loading.class="opacity-50"
        >
    @else
        <div class="bg-gray-100 aspect-square flex items-center justify-center text-gray-400">
            Enter content to see preview
        </div>
    @endif
</div>