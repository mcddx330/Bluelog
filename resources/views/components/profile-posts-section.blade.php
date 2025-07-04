@props(['posts', 'handle', 'current_sort', 'query_params'])

<div class="lg:w-2/3">
    @if(isset($posts) && $posts->count() > 0)
        <div class="space-y-4">
            <div class="mt-8 bg-white shadow-md rounded-lg p-6">
                <div class="flex flex-wrap gap-2 justify-center">
                    <a href="{{ route('profile.show', array_merge($query_params, ['handle' => $handle, 'sort' => 'posted_at_desc'])) }}"
                       class="px-4 py-2 rounded-md {{ $current_sort === 'posted_at_desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        全て降順
                    </a>
                    <a href="{{ route('profile.show', array_merge($query_params, ['handle' => $handle, 'sort' => 'posted_date_only_asc'])) }}"
                       class="px-4 py-2 rounded-md {{ $current_sort === 'posted_date_only_asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        全て降順 (朝から夜)
                    </a>
                    <a href="{{ route('profile.show', array_merge($query_params, ['handle' => $handle, 'sort' => 'posted_at_asc'])) }}"
                       class="px-4 py-2 rounded-md {{ $current_sort === 'posted_at_asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        全て昇順
                    </a>
                </div>

                <div class="mt-4">
                    {{ $posts->links() }}
                </div>

                @php
                    $currentDate = null;
                    $postsGroupedByDate = $posts->groupBy(function ($post) {
                        return $post->posted_at->format('Y-m-d');
                    });
                @endphp

                @foreach($postsGroupedByDate as $date => $dailyPosts)
                    @php
                        $dateObj = \Carbon\Carbon::parse($date);
                    @endphp
                    <div class="bg-white border border-gray-300 p-6 mt-3 mb-3">
                        <h3 class="text-xl font-bold mb-4">
                            {{ $dateObj->format('Y年m月d日') }} ({{ $dailyPosts->count() }} posts)
                        </h3>
                        <div class="space-y-4">
                            @foreach($dailyPosts as $post)
                                <div class="bg-white shadow-md rounded-lg p-4">
                                    @if($post->reply_to_handle)
                                        <p class="text-gray-500 text-sm mb-1">
                                            <a href="https://bsky.app/profile/{{ $post->reply_to_handle }}"
                                               class="text-blue-500 hover:underline">
                                                {{ "@". $post->reply_to_handle }}
                                            </a>
                                        </p>
                                    @endif
                                    <p class="text-gray-800 text-sm mb-2 whitespace-pre-wrap">@renderBlueskyText($post->text)</p>
                                    @if($post->media->count() > 0)
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-{{ $post->media->count() > 2 ? '3' : $post->media->count() }} lg:grid-cols-{{ $post->media->count() > 3 ? '4' : $post->media->count() }} gap-2 mt-2">
                                            @foreach($post->media as $media)
                                                <div class="relative">
                                                    @switch($media->type)
                                                        @case("app.bsky.embed.images")
                                                            <a href="{{ $media->fullsize_url }}" target="_blank">
                                                                <img src="{{ $media->fullsize_url }}"
                                                                     alt="{{ $media->alt_text }}"
                                                                     class="post-image w-full h-auto rounded-lg object-cover">
                                                            </a>
                                                            @break
                                                        @case("app.bsky.embed.video")
                                                            <video data-src="{{ $media->fullsize_url }}"
                                                                   alt="{{ $media->alt_text }}"
                                                                   controls
                                                                   class="post-video w-full h-auto rounded-lg object-cover">
                                                            </video>
                                                            @break
                                                        @default @break
                                                    @endswitch
                                                    @if($media->alt_text)
                                                        <div
                                                            class="absolute bottom-0 left-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-br-lg rounded-tl-lg">
                                                            {{ $media->alt_text }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <span class="text-sm text-gray-500 mt-2">{{ $post->posted_at->format('Y-m-d H:i:s') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="mt-4">
                    {{ $posts->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
