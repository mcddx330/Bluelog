@props([
    'user',
    'profile',
    'handle',
])

<div class="lg:w-2/3">
    <h1 class="text-2xl font-bold mb-4">設定</h1>

    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('status') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg p-6 mt-6">
        <h2 class="text-xl font-bold mb-4">データ更新</h2>
        <p class="text-sm text-gray-500 mb-2">
            手動でBlueSkyからデータを取得し、最新状態にします。
        </p>
        <form action="{{ route('profile.updateProfileData', ['handle' => $profile['handle']]) }}" method="POST">
            @csrf
            <button
                type="submit"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                最新状態に更新
            </button>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6 mt-6">
        <h2 class="text-xl font-bold mb-4">Bluelogの非公開</h2>
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="is_private" class="inline-flex items-center">
                    <input type="hidden" name="is_private" value="0">
                    <input type="checkbox" id="is_private" name="is_private" value="1"
                           {{ $user->is_private ? 'checked' : '' }} class="rounded h-5 w-5 text-blue-600">
                    <span class="ml-2 text-gray-700">Bluelogのプロフィールを非公開にする</span>
                </label>
                <p class="text-sm text-gray-500 mt-1">
                    チェックを入れると、あなたのプロフィール、投稿、いいねの一覧はあなた自身にしか表示されなくなります。</p>
            </div>

            <div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    保存
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6 mt-6">
        <h2 class="text-xl font-bold mb-4">データエクスポート</h2>
        <p class="text-sm text-gray-700 mb-4">
            あなたのBluelogに保存されている投稿データをCSV形式でエクスポートします。
        </p>
        <form action="{{ route('settings.exportPosts') }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                投稿データをエクスポート
            </button>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6 mt-6">
        <h2 class="text-xl font-bold mb-4 text-red-600">全件再取得</h2>
        <p class="text-sm text-gray-700 mb-4">
            Blueskyの全データを削除・再取得し、Bluelogを最新にリフレッシュします。<br />
            Bluelog上で削除したポストも、Blueskyに存在していた場合、復活します。<br />
            <strong class="font-bold text-red-700">この操作は元に戻せません。</strong>
        </p>
        <form action="{{ route('settings.fullSyncData', ['handle' => $profile['handle']]) }}" method="POST"
              onsubmit="return confirm('本当に全件再取得を実行してもよろしいですか？\nこの操作は元に戻せません。データ量によっては時間がかかります。');">
            @csrf
            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                全件再取得を実行
            </button>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6 mt-6">
        <h2 class="text-xl font-bold mb-4 text-red-600">アカウント削除</h2>
        <p class="text-sm text-gray-700 mb-4">
            アカウントを削除すると、あなたのBluelog上の全てのデータ（投稿、いいね、統計など）が完全に削除されます。
        </p>
        <form action="{{ route('settings.destroy') }}" method="POST"
              onsubmit="return confirm('本当にアカウントを削除してもよろしいですか？\nこの操作は元に戻せません。');">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                アカウントを削除
            </button>
        </form>
    </div>
</div>
