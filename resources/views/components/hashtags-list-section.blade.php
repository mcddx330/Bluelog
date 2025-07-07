@props(['hashtags', 'handle'])

<div class="lg:w-2/3">
    @if($hashtags?->count() > 0)
        <div class="space-y-4">
            <div class="mt-8 bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">並び替え</h2>
                <div class="flex space-x-4">
                    <a href="{{ route('profile.hashtags', ['handle' => $handle, 'sort_by' => 'count', 'order' => 'desc']) }}"
                       class="px-4 py-2 rounded-md {{ request('sort_by', 'count') === 'count' && request('order', 'desc') === 'desc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        使用数が多い順
                    </a>
                    <a href="{{ route('profile.hashtags', ['handle' => $handle, 'sort_by' => 'tag', 'order' => 'asc']) }}"
                       class="px-4 py-2 rounded-md {{ request('sort_by') === 'tag' && request('order') === 'asc' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }}">
                        アルファベット順
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-md rounded-lg p-6">
                <ul class="divide-y divide-gray-200">
                    @foreach($hashtags as $hashtag)
                        <li class="py-3 flex justify-between items-center">
                            <a href="https://bsky.app/search?q=%23{{ $hashtag->tag }}" target="_blank"
                               class="text-blue-500 hover:underline text-lg">
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
        </div>
    @else
        <p class="bg-white shadow-md rounded-lg p-6">ハッシュタグデータがありません。</p>
    @endif
</div>
