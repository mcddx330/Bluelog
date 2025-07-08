@props(['user', 'handle', 'archives', 'top_replies', 'top_hashtags'])

<div class="lg:w-1/3">
    <div class="mt-8 bg-white shadow-md rounded-lg p-3">
        <form action="{{ route('profile.show', ['handle' => $handle]) }}" method="GET" class="flex items-center">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="text" name="search_text" placeholder="投稿を検索..."
                   class="flex-grow p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   value="{{ request('search_text') }}">
            <button type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                検索
            </button>
            @if(request('search_text'))
                <a href="{{ route('profile.show', ['handle' => $handle, 'sort' => request('sort')]) }}"
                   class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    クリア
                </a>
            @endif
        </form>
    </div>

    <div class="mt-8 bg-white shadow-md rounded-lg p-3">
        <h2 class="text-xl font-bold pl-2 pb-2 border-b border-gray-300">アーカイブ</h2>
        <div class="w-full">
            @foreach($archives as $archive)
                <div class="flex items-center justify-between py-2 border-b border-gray-200">
                    <div class="flex-grow text-left pl-2">
                        <a href="{{ route('profile.show', ['handle' => $handle, 'archive_ym' => $archive['ym']]) }}"
                           class="text-blue-500 hover:underline">
                            {{ $archive['label'] }}
                        </a>
                    </div>
                    <div class="flex-none text-right pl-2">
                        {{ number_format($archive['count']) }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 text-right">
            <a href="{{ route('profile.archives', ['handle' => $handle]) }}" class="text-blue-500 hover:underline">
                すべてを表示
            </a>
        </div>
    </div>

    <div class="mt-8 bg-white shadow-md rounded-lg p-3">
        <h2 class="text-xl font-bold pl-2 pb-2 border-b border-gray-300">リプライ</h2>
        <div class="w-full">
            @foreach($top_replies as $reply)
                <div class="flex items-center justify-between py-2 border-b border-gray-200 sidebar-reply-profile-link">
                    <div class="flex-none text-right pr-2">
                        @if($reply->reply_to_user)
                            <a href="{{ route('profile.show', ['handle' => $reply->reply_to_handle]) }}">
                                @endif
                                <span class="text-blue-950 p-2
                                    @if($reply->reply_to_user)
                                        hover:bg-blue-100
                                    @endif
                                 ">
                                <i class="fa-user
                                    @if($reply->reply_to_user)
                                        fa-solid
                                    @else
                                        fa-regular
                                   @endif
                                "></i>
                                @if($reply->reply_to_user)
                            </a>
                        @endif
                    </div>
                    <div class="flex-grow text-left">
                        <a
                            @if($reply->reply_to_user)
                                href="{{ route('profile.show', ['handle' => $reply->reply_to_handle]) }}"
                            @else
                                href="https://bsky.app/profile/{{ $reply->reply_to_handle }}"
                            @endif
                            class="text-blue-500 hover:underline">
                            {{ "@". $reply->reply_to_handle }}
                        </a>
                    </div>
                    <div class="flex-none text-right pl-2">
                        {{ number_format($reply->reply_count) }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 text-right">
            <a href="{{ route('profile.replies', ['handle' => $handle]) }}" class="text-blue-500 hover:underline">
                すべてを表示
            </a>
        </div>
    </div>

    <div class="mt-8 bg-white shadow-md rounded-lg p-3">
        <h2 class="text-xl font-bold pl-2 pb-2 border-b border-gray-300">ハッシュタグ</h2>
        <div class="w-full">
            @foreach($top_hashtags as $hashtag)
                <div class="flex items-center justify-between py-2 border-b border-gray-200 ">
                    <div class="flex-grow text-left pl-2">
                        <a href="https://bsky.app/search?q=%23{{ $hashtag->tag }}" target="_blank"
                           class="text-blue-500 hover:underline">
                            #{{ $hashtag->tag }}
                        </a>
                    </div>
                    <div class="flex-none text-right pl-2">
                        {{ number_format($hashtag->count) }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 text-right">
            <a href="{{ route('profile.hashtags', ['handle' => $handle]) }}" class="text-blue-500 hover:underline">
                すべてを表示
            </a>
        </div>
    </div>
</div>
