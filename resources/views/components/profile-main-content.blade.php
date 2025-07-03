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
                <img src="{{ $profile['avatar'] }}" alt="Avatar" class="w-16 h-16 rounded-full border-2 border-white">
            @endif
            <div>
                <h2 class="text-xl font-semibold">
                    <a href="https://bsky.app/profile/{{ $profile['handle'] }}" target="_blank"
                       class="hover:underline">
                        {{ $profile['display_name'] ?? $profile['handle'] }}
                    </a>
                </h2>
                <p class="text-gray-300">
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
<div class="bg-white shadow-md rounded-b-lg p-3">
    <div class="pl-3 pr-3">
        <div class="flex space-x-4 rounded-lg text-black">
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

        <div class="mt-4 flex space-x-4">
            <div id="heatmap-container" class="flex flex-wrap gap-1">
                <!-- Heatmap cells will be generated here -->
            </div>
            <div id="heatmap-tooltip" class="absolute bg-gray-800 text-white text-xs p-2 rounded-md shadow-lg hidden z-50"></div>
        </div>
    </div>
</div>
