<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Likes for {{ $handle }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Likes for <a href="{{ route('profile.show', ['handle' => $handle]) }}"
                                                     class="text-blue-500 hover:underline">{{ $handle }}</a></h1>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if(isset($posts) && count($posts) > 0)
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Liked Posts</h2>
            <div class="space-y-4">
                @foreach($posts as $post)
                    <div class="bg-white shadow-md rounded-lg p-4">
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
            </div>
            {{-- ページネーションリンク --}}
            <div class="mt-4">
                {{ $likes_pagination->links() }}
            </div>
        </div>
    @else
        <p>No liked posts found.</p>
    @endif
</div>
{{-- Blueskyの埋め込み用JavaScriptを読み込む --}}
<script async src="https://embed.bsky.app/static/embed.js" charset="utf-8"></script>
</body>
</html>
