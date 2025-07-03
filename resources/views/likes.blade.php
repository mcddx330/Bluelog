@extends('layouts.app')

@section('title', 'Likes for ' . $handle)

@push('scripts')
    <script async src="https://embed.bsky.app/static/embed.js" charset="utf-8"></script>
@endpush

@section('content')
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <x-profile-main-content :profile="$profile" :handle="$handle"/>

    @if(isset($posts) && count($posts) > 0)
        <div class="mt-8">
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
@endsection
