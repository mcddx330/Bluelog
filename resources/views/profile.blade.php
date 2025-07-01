<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bluesky Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Bluesky Profile</h1>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if(isset($profile))
        @if($is_fetching)
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">データ取得中...</strong>
                <span class="block sm:inline">最新のBlueskyデータをバックグラウンドで取得しています。しばらくお待ちください。</span>
            </div>
        @endif

        <div class="relative h-48 bg-cover bg-center rounded-lg overflow-hidden shadow-md" style="background-image: url('{{ $profile['banner'] ?? 'https://via.placeholder.com/800x200?text=No+Banner+Image' }}');">
            <div class="absolute inset-0 bg-black bg-opacity-50 p-6 flex flex-col justify-end text-white">
                <div class="flex items-center space-x-4">
                    @if(isset($profile['avatar']))
                        <img src="{{ $profile['avatar'] }}" alt="Avatar" class="w-16 h-16 rounded-full border-2 border-white">
                    @endif
                    <div>
                        <h2 class="text-xl font-semibold">
                            <a href="{{ route('profile.show', $profile['handle']) }}" class="text-white hover:underline">
                                {{ $profile['display_name'] ?? $profile['handle'] }}
                            </a>
                        </h2>
                        <p class="text-gray-300">
                            <a href="https://bsky.app/profile/{{ $profile['handle'] }}" target="_blank" class="text-gray-300 hover:underline">
                                {{ "@". $profile['handle'] }}
                            </a>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-gray-200">{!! nl2br($profile['description']) ?? '' !!}</p>
                </div>
                <div class="mt-4 flex space-x-4">
                    <div>
                        <span class="font-bold">{{ $profile['followers_count'] ?? 0 }}</span>
                        <span class="text-gray-300">Followers</span>
                    </div>
                    <div>
                        <span class="font-bold">{{ $profile['follows_count'] ?? 0 }}</span>
                        <span class="text-gray-300">Following</span>
                    </div>
                    <div>
                        <span class="font-bold">{{ $profile['posts_count'] }}</span>
                        <span class="text-gray-300">Posts</span>
                    </div>
                    <div>
                        <a href="{{ route('profile.likes', ['handle' => $profile['handle']]) }}" class="text-blue-300 hover:underline">
                            <span class="font-bold">Likes</span>
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('profile.status', ['handle' => $profile['handle']]) }}" class="text-blue-300 hover:underline">
                            <span class="font-bold">Status</span>
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('profile.friends', ['handle' => $profile['handle']]) }}" class="text-blue-300 hover:underline">
                            <span class="font-bold">Friends</span>
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('profile.hashtags', ['handle' => $profile['handle']]) }}" class="text-blue-300 hover:underline">
                            <span class="font-bold">Hashtags</span>
                        </a>
                    </div>
                    @auth
                        @if(Auth::user()->handle === $handle)
                            <div>
                                <a href="{{ route('settings.edit') }}" class="text-blue-300 hover:underline">
                                    <span class="font-bold">設定</span>
                                </a>
                            </div>
                            <div>
                                <form action="{{ route('logout') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-300 hover:underline font-bold">ログアウト</button>
                                </form>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    @else
        <p>No profile data available.</p>
    @endif

    <div class="mt-8 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4">投稿ヒートマップ</h2>
        <div id="heatmap-container" class="flex flex-wrap gap-1">
            <!-- Heatmap cells will be generated here -->
        </div>
        <div id="heatmap-tooltip" class="absolute bg-gray-800 text-white text-xs p-2 rounded-md shadow-lg hidden z-50"></div>
    </div>

    <div class="mt-8 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4">投稿検索</h2>
        <form action="{{ route('profile.show', ['handle' => $handle]) }}" method="GET" class="flex items-center space-x-2">
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

    <div class="mt-8 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4">並び替え</h2>
        <div class="flex flex-wrap gap-2">
            @php
                $currentSort = request('sort', 'posted_at_desc');
                $queryParams = request()->except(['sort', 'page']);
            @endphp
            <a href="{{ route('profile.show', array_merge($queryParams, ['handle' => $handle, 'sort' => 'posted_at_desc'])) }}"
               class="px-4 py-2 rounded-md {{ $currentSort === 'posted_at_desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                全て降順
            </a>
            <a href="{{ route('profile.show', array_merge($queryParams, ['handle' => $handle, 'sort' => 'posted_date_only_asc'])) }}"
               class="px-4 py-2 rounded-md {{ $currentSort === 'posted_date_only_asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                全て降順 (朝から夜)
            </a>
            <a href="{{ route('profile.show', array_merge($queryParams, ['handle' => $handle, 'sort' => 'posted_at_asc'])) }}"
               class="px-4 py-2 rounded-md {{ $currentSort === 'posted_at_asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                全て昇順
            </a>
        </div>
    </div>

    @if(isset($archives) && count($archives) > 0)
        <div class="mt-8 bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4">アーカイブ</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($archives as $archive)
                    <a href="{{ route('profile.show', ['handle' => $handle, 'archive_ym' => $archive['ym']]) }}"
                       class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded hover:bg-blue-200">
                        {{ $archive['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($top_mentions) && $top_mentions->count() > 0)
        <div class="mt-8 bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4">メンション</h2>
            <ul class="list-disc pl-5">
                @foreach($top_mentions as $mention)
                    <li>
                        <a href="https://bsky.app/profile/{{ $mention->reply_to_handle }}" target="_blank"
                           class="text-blue-500 hover:underline">
                            {{ "@". $mention->reply_to_handle }}
                        </a>
                        ({{ number_format($mention->mention_count) }} 回)
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
        <div class="mt-8 bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4">ハッシュタグ</h2>
            <ul class="list-disc pl-5">
                @foreach($top_hashtags as $hashtag)
                    <li>
                        <a href="https://bsky.app/search?q=%23{{ $hashtag->tag }}" target="_blank" class="text-blue-500 hover:underline">
                            #{{ $hashtag->tag }}
                        </a>
                        ({{ number_format($hashtag->count) }} 回)
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

    @if(isset($posts) && $posts->count() > 0)
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Posts</h2>
            <div class="space-y-4">
                @foreach($posts as $post)
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
            {{-- ページネーションリンク --}}
            <div class="mt-4">
                {{ $posts->links() }}
            </div>
        </div>
    @endif
</div>


<script>
    document.querySelectorAll('.post-video').forEach(function (video) {
        if (Hls.isSupported()) {
            var hls = new Hls();
            var videoSrc = video.getAttribute('data-src');
            hls.loadSource(videoSrc);
            hls.attachMedia(video);
        } else {
            video.innerHTML = 'お使いのブラウザはHLS(HTTP Live Streaming)をサポートしていません。';
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const dailyStats = JSON.parse('{!! $dailyStats !!}');
        const heatmapContainer = document.getElementById('heatmap-container');
        const heatmapTooltip = document.getElementById('heatmap-tooltip');

        // Define color scale (adjust as needed)
        const colors = [
            '#ebedf0', // No posts
            '#9be9a8', // 1-5 posts
            '#40c463', // 6-10 posts
            '#30a14e', // 11-15 posts
            '#216e39'  // 16+ posts
        ];

        // Determine max posts for dynamic scaling (optional, but good for varied data)
        let maxPosts = 0;
        Object.values(dailyStats).forEach(count => {
            if (count > maxPosts) {
                maxPosts = count;
            }
        });

        // Function to get color based on post count
        function getColor(count) {
            if (count === 0) return colors[0];
            const step = maxPosts > 0 ? maxPosts / (colors.length - 1) : 1;
            let colorIndex = Math.ceil(count / step);
            if (colorIndex >= colors.length) colorIndex = colors.length - 1;
            if (colorIndex < 1) colorIndex = 1;
            return colors[colorIndex];
        }

        // Generate dates for the last year
        const today = new Date();
        const oneYearAgo = new Date();
        oneYearAgo.setFullYear(today.getFullYear() - 1);

        let currentDate = new Date(oneYearAgo);
        while (currentDate <= today) {
            const dateString = currentDate.toISOString().slice(0, 10); // YYYY-MM-DD
            const postsCount = dailyStats[dateString] || 0;
            const color = getColor(postsCount);

            const cell = document.createElement('div');
            cell.className = 'w-3 h-3 rounded-sm relative'; // Add relative for positioning tooltip if needed
            cell.style.backgroundColor = color;
            cell.dataset.date = dateString;
            cell.dataset.posts = postsCount;

            cell.addEventListener('mouseover', function (e) {
                const date = this.dataset.date;
                const posts = this.dataset.posts;
                const formattedDate = date.replace(/(\d{4})-(\d{2})-(\d{2})/, '$1/$2/$3');
                heatmapTooltip.textContent = `${formattedDate}: ${posts}件`;
                heatmapTooltip.style.left = `${e.pageX + 10}px`;
                heatmapTooltip.style.top = `${e.pageY + 10}px`;
                heatmapTooltip.classList.remove('hidden');
            });

            cell.addEventListener('mouseout', function () {
                heatmapTooltip.classList.add('hidden');
            });

            cell.addEventListener('click', function () {
                const date = this.dataset.date;
                const handle = '{{ $handle }}'; // Blade variable for handle
                window.location.href = `{{ route('profile.show', ['handle' => $handle]) }}?date=${date}`;
            });

            heatmapContainer.appendChild(cell);

            currentDate.setDate(currentDate.getDate() + 1); // Move to next day
        }
    });
</script>
</body>
</html>
