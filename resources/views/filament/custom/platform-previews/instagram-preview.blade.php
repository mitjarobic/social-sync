<div class="instagram-preview rounded-lg overflow-hidden shadow-md bg-white max-w-md mx-auto border border-gray-200">
    @if($platform)
        <!-- Instagram header -->
        <div class="p-2 border-b border-gray-100 text-center">
            <div class="text-lg font-semibold text-gray-500">Instagram</div>
        </div>

        <!-- User info -->
        <div class="p-3 flex items-center">
            <div class="w-8 h-8 rounded-full overflow-hidden">
                @if($platform->external_picture_url)
                    <img src="{{ asset('storage/' . $platform->external_picture_url) }}" alt="Profile" class="w-full h-full object-cover">
                @else
                    <img src="https://via.placeholder.com/32x32" alt="Profile" class="w-full h-full object-cover">
                @endif
            </div>
            <div class="ml-2 font-semibold text-sm text-gray-500">{{ $platform->external_name }}</div>
        </div>

        <!-- Image -->
        @if(!empty($imageContent))
            <div>
                <img
                    src="/generate-image?content={{ urlencode($imageContent ?? '') }}&author={{ urlencode($author ?? '') }}&contentFont={{ $contentFont ?? 'sansSerif.ttf' }}&contentFontSize={{ $contentFontSize ?? 112 }}&contentFontColor={{ urlencode($contentFontColor ?? '#FFFFFF') }}&authorFont={{ $authorFont ?? 'sansSerif.ttf' }}&authorFontSize={{ $authorFontSize ?? 78 }}&authorFontColor={{ urlencode($authorFontColor ?? '#FFFFFF') }}&bgColor={{ urlencode($bgColor ?? '#000000') }}&bgImagePath={{ urlencode($bgImagePath ?? '') }}&v={{ $version ?? time() }}"
                    class="w-full aspect-square object-cover"
                    wire:loading.class="opacity-50"
                >
            </div>
        @endif

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
            <span class="font-semibold">{{ $platform->external_name }}</span> {!! nl2br(e($content ?? '')) !!}
        </div>
    @else
        <!-- No Instagram platform message -->
        <div class="p-6 text-center">
            <div class="text-gray-400 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.024-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.097.118.112.221.083.343-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001.017 0z"/>
                </svg>
            </div>
            <div class="text-gray-600 font-medium">No Instagram Platform</div>
            <div class="text-gray-500 text-sm">Add an Instagram platform in the Platforms section to see preview</div>
        </div>
    @endif
</div>
