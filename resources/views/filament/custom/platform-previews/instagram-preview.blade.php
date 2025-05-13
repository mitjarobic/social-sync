<div class="instagram-preview rounded-lg overflow-hidden shadow-md bg-white max-w-md mx-auto border border-gray-200">
    <!-- Instagram header -->
    <div class="p-2 border-b border-gray-100 text-center">
        <div class="text-lg font-semibold text-gray-500">Instagram</div>
    </div>

    <!-- User info -->
    <div class="p-2 flex items-center">
        <div class="w-8 h-8 rounded-full overflow-hidden">
            <img src="https://via.placeholder.com/32x32" alt="Profile" class="w-full h-full object-cover">
        </div>
        <div class="ml-2 font-semibold text-sm text-gray-500">earthairintuitive</div>
    </div>

    <!-- Image -->
    <div>
        <img
            src="/generate-image?content={{ urlencode($imageContent ?? '') }}&author={{ urlencode($author ?? '') }}&font={{ $font ?? 'sansSerif.ttf' }}&fontSize={{ $fontSize ?? 112 }}&fontColor={{ urlencode($fontColor ?? '#FFFFFF') }}&bgColor={{ urlencode($bgColor ?? '#000000') }}&bgImagePath={{ urlencode($bgImagePath ?? '') }}&v={{ $version ?? time() }}"
            class="w-full aspect-square object-cover"
            wire:loading.class="opacity-50"
        >
    </div>

    <!-- Action buttons -->
    <div class="p-3 flex justify-between">
        <div class="flex space-x-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
            </svg>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
            </svg>
        </div>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
        </svg>
    </div>

    <!-- Caption -->
    <div class="px-3 pb-3 text-sm text-gray-500">
        <span class="font-semibold">earthairintuitive</span> {{ $content ?? '' }}
    </div>
</div>
