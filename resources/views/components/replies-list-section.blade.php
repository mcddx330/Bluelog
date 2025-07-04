@props(['replies', 'handle', 'order', 'sort_by'])

<div class="lg:w-2/3">
    @if($replies?->count() > 0)
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="space-y-4">
                <div class="flex space-x-4 justify-center">
                    <a href="{{ route('profile.replies', ['handle' => $handle, 'sort_by' => 'count', 'order' => 'desc']) }}"
                       class="px-4 py-2 rounded-md {{ $sort_by === 'count' && $order === 'desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        リプライ数 (降順)
                    </a>
                    <a href="{{ route('profile.replies', ['handle' => $handle, 'sort_by' => 'count', 'order' => 'asc']) }}"
                       class="px-4 py-2 rounded-md {{ $sort_by === 'count' && $order === 'asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        リプライ数 (昇順)
                    </a>
                    <a href="{{ route('profile.replies', ['handle' => $handle, 'sort_by' => 'handle', 'order' => 'asc']) }}"
                       class="px-4 py-2 rounded-md {{ $sort_by === 'handle' && $order === 'asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        ハンドル名 (昇順)
                    </a>
                    <a href="{{ route('profile.replies', ['handle' => $handle, 'sort_by' => 'handle', 'order' => 'desc']) }}"
                       class="px-4 py-2 rounded-md {{ $sort_by === 'handle' && $order === 'desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        ハンドル名 (降順)
                    </a>
                </div>
                <div class="mt-4">
                    {{ $replies->links() }}
                </div>
                <ul class="divide-y divide-gray-200">
                    @foreach($replies as $reply)
                        <li class="py-3 flex justify-between items-center">
                            <a href="https://bsky.app/profile/{{ $reply->reply_to_handle }}" target="_blank"
                               class="text-blue-500 hover:underline text-lg">
                                {{ "@". $reply->reply_to_handle }}
                            </a>
                            <span class="text-gray-700 text-lg">{{ number_format($reply->reply_count) }} 件</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">
                    {{ $replies->links() }}
                </div>
            </div>
        </div>
    @else
        <p class="bg-white shadow-md rounded-lg p-6">リプライデータがありません。</p>
    @endif
</div>
