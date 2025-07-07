@props([
    'daily_stats',
    'profile',
    'handle',
    'user',
])

<div class="relative bg-cover bg-center rounded-t-lg overflow-hidden shadow-md"
     style="
        background-image: url('{{ $profile['banner'] ?? 'https://via.placeholder.com/800x200?text=No+Banner+Image' }}');
        min-height: 240px;
    ">
    <div
        id="profile-header-overlay"
        class="
            absolute
            inset-0
            bg-black
            bg-opacity-50
            pl-6
            pr-6
            pt-2
            pb-2
            flex
            flex-col
            justify-center
            text-white
            backdrop-blur-sm
        "
    >
        <div class="flex items-center space-x-4">
            @if(isset($profile['avatar']))
                <img src="{{ $profile['avatar'] }}" alt="Avatar" class="w-20 h-20 rounded-full border-4 border-white">
            @endif
            <div>
                <h2 class="text-xl font-semibold">
                    <a href="https://bsky.app/profile/{{ $profile['handle'] }}" target="_blank"
                       class="hover:underline">
                        {{ $profile['display_name'] ?? $profile['handle'] }}
                    </a>
                </h2>
                <p class="text-white">
                    <a href="https://bsky.app/profile/{{ $profile['handle'] }}" target="_blank"
                       class="hover:underline">
                        {{ "@". $profile['handle'] }}
                    </a>
                </p>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-white">{{ ($profile['description']) ?? '' }}</p>
        </div>
    </div>
</div>
<div
    id="header-main-links"
    class="
        bg-white
        shadow-md
        p-3
    ">
    <div class="pl-3 pr-3">
        <div class="flex justify-between items-center rounded-lg text-black">
            <div class="flex space-x-4">
                <div class="bg-opacity-50 p-2 rounded-md">
                    <span class="font-bold">{{ $profile['followers_count'] ?? 0 }}</span>
                    <span class="">フォロワー</span>
                </div>
                <div class="bg-opacity-50 p-2 rounded-md">
                    <span class="font-bold">{{ $profile['follows_count'] ?? 0 }}</span>
                    <span class="">フォロー</span>
                </div>
                <div class="bg-opacity-50 p-2 rounded-md">
                    <a href="{{ route('profile.show', ['handle' => $profile['handle']]) }}" class="text-blue-600 hover:underline">
                        <span class="font-bold">{{ $profile['posts_count'] }}</span>
                        <span class="">ポスト</span>
                    </a>
                </div>
                <div class="bg-opacity-50 p-2 rounded-md">
                    <a href="{{ route('profile.likes', ['handle' => $profile['handle']]) }}" class="text-blue-600 hover:underline">
                        <span class="font-bold">{{ $profile['likes_count'] }}</span>
                        <span class="">いいね</span>
                    </a>
                </div>
                <div class="bg-opacity-50 p-2 rounded-md">
                    <a href="{{ route('profile.status', ['handle' => $profile['handle']]) }}" class="text-blue-600 hover:underline">
                        <span class="font-bold">ステータス</span>
                    </a>
                </div>
            </div>
            @php
                $last_fetched_at = Auth::user()?->last_fetched_at;
                if (is_null($last_fetched_at)) {
                    $last_fetched_at = $user->last_fetched_at;
                }
                $last_fetched_at = $last_fetched_at?->format('Y/m/d H:i:s') ?? '-';
            @endphp
            <div class="text-sm">
                <span class="">BlueSky活動歴：{{ number_format($user->total_days_from_registered_bluesky) }}日</span>
                @if(Auth::user()?->did === $profile['did'])
                    /
                    <span class="">最終更新：{{ $last_fetched_at }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if(Route::currentRouteName() === 'profile.show' && !$daily_stats->isEmpty())
    <div class="bg-white shadow-md rounded-b-lg pb-3 pl-3 pr-3">
        <div id="heatmap-container" class="flex flex-wrap gap-1">
            <!-- Heatmap cells will be generated here -->
        </div>
        <div id="heatmap-tooltip" class="absolute bg-gray-800 text-white text-xs p-2 rounded-md shadow-lg hidden z-50"></div>
    </div>
@endif

@if(Route::currentRouteName() === 'profile.show')
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
        <script>
            const daily_stats = JSON.parse('{!! $daily_stats->toJson() !!}');

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
                Object.values(daily_stats).forEach(count => {
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
                oneYearAgo.setFullYear(today.getFullYear() - 1, 0, 1);

                let currentDate = new Date(oneYearAgo);
                while (currentDate <= today) {
                    const dateString = currentDate.toISOString().slice(0, 10); // YYYY-MM-DD
                    const postsCount = daily_stats[dateString] || 0;
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
    @endpush
@endif
