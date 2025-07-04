@props(['posts', 'likes_pagination'])

<div class="lg:w-2/3">
    <div>
        <div class="space-y-4">
            <div class="mt-8 bg-white shadow-md rounded-lg p-6">
                {{ $likes_pagination->links() }}

                @foreach($posts as $post)
                    <div class="bg-white border border-gray-300 p-6 mt-3 mb-3">
                        <p class="text-sm text-gray-500 mb-2">Liked at: {{ $post['liked_at']->format('Y-m-d H:i:s') }}</p>
                        {{-- Bluesky公式の埋め込みスニペットを使用 --}}
                        <blockquote class="bluesky-embed"
                                    data-bluesky-uri="{{ $post['bluesky_uri'] }}"
                                    data-bluesky-cid="{{ $post['bluesky_cid'] }}"
                                    data-bluesky-embed-color-mode="system">
                            <p>Loading Bluesky post...</p>
                        </blockquote>
                    </div>
                @endforeach

                <div class="mt-4">
                    {{ $likes_pagination->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
