@extends('layouts.app')

@section('title', 'Bluesky Hashtag Ranking')

@section('content')
    <h1 class="text-2xl font-bold mb-4">{{ "@". $user->handle }} のハッシュタグランキング</h1>

    <div class="bg-white shadow-md rounded-lg p-6 mb-4">
        <h2 class="text-xl font-bold mb-4">並び替え</h2>
        <div class="flex space-x-4">
            <a href="{{ route('profile.hashtags', ['handle' => $user->handle, 'sort_by' => 'count', 'order' => 'desc']) }}"
               class="px-4 py-2 rounded-md {{ request('sort_by') === 'count' && request('order') === 'desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                ハッシュタグ数 (降順)
            </a>
            <a href="{{ route('profile.hashtags', ['handle' => $user->handle, 'sort_by' => 'count', 'order' => 'asc']) }}"
               class="px-4 py-2 rounded-md {{ request('sort_by') === 'count' && request('order') === 'asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                ハッシュタグ数 (昇順)
            </a>
            <a href="{{ route('profile.hashtags', ['handle' => $user->handle, 'sort_by' => 'tag', 'order' => 'asc']) }}"
               class="px-4 py-2 rounded-md {{ request('sort_by') === 'tag' && request('order') === 'asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                ハッシュタグ名 (昇順)
            </a>
            <a href="{{ route('profile.hashtags', ['handle' => $user->handle, 'sort_by' => 'tag', 'order' => 'desc']) }}"
               class="px-4 py-2 rounded-md {{ request('sort_by') === 'tag' && request('order') === 'desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                ハッシュタグ名 (降順)
            </a>
        </div>
    </div>

    @if($hashtags?->count() > 0)
        <div class="bg-white shadow-md rounded-lg p-6">
            <ul class="divide-y divide-gray-200">
                @foreach($hashtags as $hashtag)
                    <li class="py-3 flex justify-between items-center">
                        <a href="https://bsky.app/search?q=%23{{ $hashtag->tag }}" target="_blank" class="text-blue-500 hover:underline text-lg">
                            #{{ $hashtag->tag }}
                        </a>
                        <span class="text-gray-700 text-lg">{{ number_format($hashtag->count) }} 回</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">
                {{ $hashtags->links() }}
            </div>
        </div>
    @else
        <p class="bg-white shadow-md rounded-lg p-6">ハッシュタグデータがありません。</p>
    @endif

    <div class="mt-4">
        <a href="{{ route('profile.show', ['handle' => $user->handle]) }}" class="text-blue-500 hover:underline">
            &larr; プロフィールに戻る
        </a>
    </div>
@endsection
