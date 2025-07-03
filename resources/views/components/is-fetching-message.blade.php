@props(['is_fetching'])
@if($is_fetching)
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">データ取得中...</strong>
        <span class="block sm:inline">最新のBlueskyデータをバックグラウンドで取得しています。しばらくお待ちください。</span>
    </div>
@endif
