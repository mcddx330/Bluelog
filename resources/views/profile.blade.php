@extends('layouts.app')

@section('title', 'Bluesky Profile')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
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
@endpush

@section('content')
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <nav class="text-sm text-gray-400 mb-4">
        <ol class="list-none p-0 inline-flex">
            <li class="flex items-center">
                <a href="{{ route('index') }}" class=" hover:underline">
                    <i class="fas fa-home"></i>
                </a>
                <svg class="fill-current w-3 h-4 mx-2"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path
                        d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 67.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                </svg>
            </li>
            <li class="flex items-center">
                @if(Route::currentRouteName() === 'profile.show')
                    <span class="text-gray-900">{{ '@' . $profile['handle'] }}</span>
                @else
                    <a href="{{ route('profile.show', ['handle' => $profile['handle']]) }}" class="hover:underline">
                        {{ '@' . $profile['handle'] }}
                    </a>
                @endif
            </li>
        </ol>
    </nav>

    @if(isset($profile))
        <x-is-fetching-message :is_fetching="$is_fetching"/>
        <x-profile-main-content :profile="$profile" :handle="$handle"/>
        <div class="lg:flex lg:space-x-8 mt-8">
            <x-profile-posts-section :posts="$posts" :handle="$handle" :current_sort="request('sort', 'posted_at_desc')"
                                     :query_params="request()->except(['sort', 'page'])"/>
            <x-profile-sidebar :handle="$handle" :archives="$archives" :top_mentions="$top_mentions" :top_hashtags="$top_hashtags"/>
        </div>
    @else
        <p>No profile data available.</p>
    @endif
@endsection
