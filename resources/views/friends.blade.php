<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bluesky Friends Ranking</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">{{ "@". $handle }} のメンションランキング</h1>

    <div class="bg-white shadow-md rounded-lg p-6 mb-4">
        <h2 class="text-xl font-bold mb-4">並び替え</h2>
        <div class="flex space-x-4">
            <a href="{{ route('profile.friends', ['handle' => $handle, 'sort_by' => 'count', 'order' => 'desc']) }}"
               class="px-4 py-2 rounded-md {{ $sort_by === 'count' && $order === 'desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                メンション数 (降順)
            </a>
            <a href="{{ route('profile.friends', ['handle' => $handle, 'sort_by' => 'count', 'order' => 'asc']) }}"
               class="px-4 py-2 rounded-md {{ $sort_by === 'count' && $order === 'asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                メンション数 (昇順)
            </a>
            <a href="{{ route('profile.friends', ['handle' => $handle, 'sort_by' => 'handle', 'order' => 'asc']) }}"
               class="px-4 py-2 rounded-md {{ $sort_by === 'handle' && $order === 'asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                ハンドル名 (昇順)
            </a>
            <a href="{{ route('profile.friends', ['handle' => $handle, 'sort_by' => 'handle', 'order' => 'desc']) }}"
               class="px-4 py-2 rounded-md {{ $sort_by === 'handle' && $order === 'desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                ハンドル名 (降順)
            </a>
        </div>
    </div>

    @if($mentions?->count() > 0)
        <div class="bg-white shadow-md rounded-lg p-6">
            <ul class="divide-y divide-gray-200">
                @foreach($mentions as $mention)
                    <li class="py-3 flex justify-between items-center">
                        <a href="https://bsky.app/profile/{{ $mention->reply_to_handle }}" target="_blank" class="text-blue-500 hover:underline text-lg">
                            {{ "@". $mention->reply_to_handle }}
                        </a>
                        <span class="text-gray-700 text-lg">{{ number_format($mention->mention_count) }} 回</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">
                {{ $mentions->links() }}
            </div>
        </div>
    @else
        <p class="bg-white shadow-md rounded-lg p-6">メンションデータがありません。</p>
    @endif

    <div class="mt-4">
        <a href="{{ route('profile.show', ['handle' => $handle]) }}" class="text-blue-500 hover:underline">
            &larr; プロフィールに戻る
        </a>
    </div>
</div>
</body>
</html>
