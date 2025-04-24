@props(['platformPosts'])

<div class="flex flex-wrap gap-3">
    @forelse($platformPosts as $platformPost)
        <div class="flex items-center space-x-2">
            {{-- Platform Icon --}}
            <span class="flex items-center justify-center w-6 h-6">
                @if($platformPost->platform->slug === 'instagram')
                    <x-heroicon-o-camera class="h-5 w-5 text-pink-500" />
                @elseif($platformPost->platform->slug === 'facebook')
                    
                    <x-heroicon-o-camera class="h-5 w-5 text-blue-600" />
                @elseif($platformPost->platform->slug === 'twitter')
                    <x-heroicon-o-chat-bubble-left-ellipsis class="h-5 w-5 text-sky-400" />
                @else
                    <x-heroicon-o-link class="h-5 w-5 text-gray-400" />
                @endif
            </span>

            {{-- Status Badge --}}
            <span @class([
                'px-3 py-1 rounded-full text-xs font-medium',
                'bg-green-100 text-green-800' => $platformPost->status === 'published',
                'bg-red-100 text-red-800' => $platformPost->status === 'failed',
                'bg-yellow-100 text-yellow-800' => $platformPost->status === 'queued',
                'bg-gray-100 text-gray-800' => $platformPost->status === 'draft'
            ])>
                {{ ucfirst($platformPost->status) }}
            </span>
        </div>
    @empty
        <span class="text-sm text-gray-500">
            No platforms added
        </span>
    @endforelse
</div>