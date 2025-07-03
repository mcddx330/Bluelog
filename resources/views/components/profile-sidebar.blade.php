@props(['handle', 'archives', 'top_mentions', 'top_hashtags'])

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

    @if(isset($archives) && count($archives) > 0)
        <div class="mt-8 bg-white shadow-md rounded-lg p-3">
            <h2 class="text-xl font-bold mb-4">アーカイブ</h2>
            <ul class="list-disc pl-5">
                @foreach($archives as $archive)
                    <li>
                        <a href="{{ route('profile.show', ['handle' => $handle, 'archive_ym' => $archive['ym']]) }}"
                           class="text-blue-500 hover:underline">
                            {{ $archive['label'] }}
                        </a>
                        ({{ number_format($archive['count']) }})
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(isset($top_mentions) && $top_mentions->count() > 0)
        <div class="mt-8 bg-white shadow-md rounded-lg p-3">
            <h2 class="text-xl font-bold mb-4">メンション</h2>
            <ul class="list-disc pl-5">
                @foreach($top_mentions as $mention)
                    <li>
                        <a href="https://bsky.app/profile/{{ $mention->reply_to_handle }}" target="_blank"
                           class="text-blue-500 hover:underline">
                            {{ "@". $mention->reply_to_handle }}
                        </a>
                        ({{ number_format($mention->mention_count) }})
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">
                <a href="{{ route('profile.friends', ['handle' => $handle]) }}" class="text-blue-500 hover:underline">
                    全メンションランキングを見る
                </a>
            </div>
        </div>
    @endif

    @if(isset($top_hashtags) && $top_hashtags->count() > 0)
        <div class="mt-8 bg-white shadow-md rounded-lg p-3">
            <h2 class="text-xl font-bold mb-4">ハッシュタグ</h2>
            <ul class="list-disc pl-5">
                @foreach($top_hashtags as $hashtag)
                    <li>
                        <a href="https://bsky.app/search?q=%23{{ $hashtag->tag }}" target="_blank"
                           class="text-blue-500 hover:underline">
                            #{{ $hashtag->tag }}
                        </a>
                        ({{ number_format($hashtag->count) }})
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">
                <a href="{{ route('profile.hashtags', ['handle' => $handle]) }}" class="text-blue-500 hover:underline">
                    全ハッシュタグランキングを見る
                </a>
            </div>
        </div>
    @endif
</div>
