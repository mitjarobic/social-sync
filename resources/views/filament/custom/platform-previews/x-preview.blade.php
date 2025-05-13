<div class="x-preview rounded-lg overflow-hidden shadow-md bg-white max-w-md mx-auto border border-gray-200">
    <!-- X header -->
    <div class="p-3 flex items-start">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-full overflow-hidden">
                <img src="https://via.placeholder.com/40x40" alt="Profile" class="w-full h-full object-cover">
            </div>
        </div>
        <div class="ml-3 flex-1">
            <div class="flex items-center">
                <div class="font-bold text-sm text-gray-500">Earth Air Intuitive</div>
                <div class="ml-1 text-gray-500 text-sm">@earthairintuitive</div>
                <div class="ml-1 text-gray-500">·</div>
                <div class="ml-1 text-gray-500 text-sm">Just now</div>
                <div class="ml-auto text-gray-500">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 16a2 2 0 100-4 2 2 0 000 4zm0-6a2 2 0 100-4 2 2 0 000 4zm0-6a2 2 0 100-4 2 2 0 000 4z"></path>
                    </svg>
                </div>
            </div>

            <!-- Post content -->
            <div class="text-sm mt-1">
                {{ $content ?? '' }}
            </div>

            <!-- Image -->
            <div class="mt-3 rounded-xl overflow-hidden">
                <img
                    src="/generate-image?content={{ urlencode($imageContent ?? '') }}&author={{ urlencode($author ?? '') }}&font={{ $font ?? 'sansSerif.ttf' }}&fontSize={{ $fontSize ?? 112 }}&fontColor={{ urlencode($fontColor ?? '#FFFFFF') }}&bgColor={{ urlencode($bgColor ?? '#000000') }}&bgImagePath={{ urlencode($bgImagePath ?? '') }}&v={{ $version ?? time() }}"
                    class="w-full object-cover"
                    wire:loading.class="opacity-50"
                >
            </div>

            <!-- Action buttons -->
            <div class="flex justify-between mt-3 text-gray-500">
                <div class="flex items-center group">
                    <svg class="w-5 h-5 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <span class="ml-1 text-xs group-hover:text-blue-500">5</span>
                </div>
                <div class="flex items-center group">
                    <svg class="w-5 h-5 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="ml-1 text-xs group-hover:text-green-500">2</span>
                </div>
                <div class="flex items-center group">
                    <svg class="w-5 h-5 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <span class="ml-1 text-xs group-hover:text-red-500">42</span>
                </div>
                <div class="flex items-center group">
                    <svg class="w-5 h-5 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>
