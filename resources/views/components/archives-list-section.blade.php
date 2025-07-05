@props(['archives'])

<div class="lg:w-2/3">
    @if(isset($archives) && count($archives) > 0)
        <div class="space-y-4">
            <div class="mt-8 bg-white shadow-md rounded-lg p-6">
                <div class="w-full">
                    @php
                        $groupedArchives = $archives->groupBy('year');
                    @endphp

                    @foreach($groupedArchives as $year => $months)
                        <div class="py-2">
                            <h3 class="text-lg font-semibold text-gray-700">{{ $year }}年</h3>
                            <ul class="ml-4">
                                @foreach($months as $archive)
                                    <li class="flex items-center justify-between py-1 border-b border-gray-200">
                                        <div class="flex-grow text-left">
                                            <a href="{{ route('profile.show', ['handle' => request()->route('handle'), 'archive_ym' => $archive['ym']]) }}"
                                               class="text-blue-500 hover:underline">
                                                {{ $archive['year'] }}年{{ $archive['month'] }}月
                                            </a>
                                        </div>
                                        <div class="flex-none text-right">
                                            {{ number_format($archive['count']) }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <p>アーカイブデータがありません。</p>
    @endif
</div>
