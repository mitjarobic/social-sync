<div class="facebook-preview rounded-lg overflow-hidden shadow-md bg-white max-w-md mx-auto border border-gray-200">
    @if($platform)
        <!-- Facebook header with profile picture -->
        <div class="p-3 flex items-start">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full overflow-hidden mr-10">
                    @if($platform->external_picture_url)
                        <img src="{{ asset('storage/' . $platform->external_picture_url) }}" alt="Profile" class="w-full h-full object-cover">
                    @else
                        <img src="https://via.placeholder.com/40x40" alt="Profile" class="w-full h-full object-cover">
                    @endif
                </div>
            </div>
            <div class="ml-2 flex-1">
                <div class="font-bold text-sm text-gray-500">{{ $platform->external_name }}</div>
                <div class="text-xs text-gray-500 flex items-center">
                    Just now · <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-1.7 14.5l-3.2-3.2 1.4-1.4 1.8 1.8 4.5-4.5 1.4 1.4-5.9 5.9z"></path></svg>
                </div>
            </div>
            <div class="ml-auto text-gray-500">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 16a2 2 0 100-4 2 2 0 000 4zm0-6a2 2 0 100-4 2 2 0 000 4zm0-6a2 2 0 100-4 2 2 0 000 4z"></path>
                </svg>
            </div>
        </div>
    @else
        <!-- No Facebook platform message -->
        <div class="p-6 text-center">
            <div class="text-gray-400 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </div>
            <div class="text-gray-600 font-medium">No Facebook Platform</div>
            <div class="text-gray-500 text-sm">Add a Facebook platform in the Platforms section to see preview</div>
        </div>
    @endif

    <!-- Post content - always show -->
    <div class="px-3 pb-2 text-sm text-gray-500">
        {!! nl2br(e($content ?? '')) !!}
    </div>

    <!-- Image - always show if content exists -->
    @if(!empty($imageContent))
    <div>
        <img
            src="/generate-image?content={{ urlencode($imageContent ?? '') }}&author={{ urlencode($author ?? '') }}&contentFont={{ $contentFont ?? 'sansSerif.ttf' }}&contentFontSize={{ $contentFontSize ?? 112 }}&contentFontColor={{ urlencode($contentFontColor ?? '#FFFFFF') }}&authorFont={{ $authorFont ?? 'sansSerif.ttf' }}&authorFontSize={{ $authorFontSize ?? 78 }}&authorFontColor={{ urlencode($authorFontColor ?? '#FFFFFF') }}&bgColor={{ urlencode($bgColor ?? '#000000') }}&bgImagePath={{ urlencode($bgImagePath ?? '') }}&v={{ $version ?? time() }}"
            class="w-full object-cover"
            wire:loading.class="opacity-50"
        >
    </div>
    @endif

    <!-- Action buttons - always show -->
    <div class="flex justify-between p-2 text-gray-500 border-t border-gray-100">
        <div class="flex items-center space-x-1 hover:bg-gray-100 px-2 py-1 rounded-md cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.60L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
            </svg>
            <span class="text-sm">Like</span>
        </div>
        <div class="flex items-center space-x-1 hover:bg-gray-100 px-2 py-1 rounded-md cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <span class="text-sm">Comment</span>
        </div>
        <div class="flex items-center space-x-1 hover:bg-gray-100 px-2 py-1 rounded-md cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
            </svg>
            <span class="text-sm">Share</span>
        </div>
    </div>
</div>
