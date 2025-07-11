@props([
    'user',
    'profile',
    'handle',
    'invitation_codes',
    'registration_mode',
    'allowed_single_user_did',
    'all_users',
])

<div class="lg:w-2/3">
    <h1 class="text-2xl font-bold mb-4">設定</h1>

    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('status') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
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

            <div class="mb-4">
                <label for="invisible_badge" class="inline-flex items-center">
                    <input type="hidden" name="invisible_badge" value="0">
                    <input type="checkbox" id="invisible_badge" name="invisible_badge" value="1"
                           {{ $user->invisible_badge ? 'checked' : '' }} class="rounded h-5 w-5 text-blue-600">
                    <span class="ml-2 text-gray-700">アカウントステータスバッジを非表示にする</span>
                </label>
                <p class="text-sm text-gray-500 mt-1">
                    チェックを入れると、プロフィールに表示される「Bluelogにおける特典」を表すバッジが非表示になります。</p>
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

    @if ($user->is_admin)
        <div class="bg-white shadow-md rounded-lg p-6 mt-6">
            <h2 class="text-xl font-semibold mb-4">利用者設定</h2>
            <form action="{{ route('settings.updateRegistrationMode') }}" method="POST" class="space-y-4">
                @csrf
                <div class="mb-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="registration_mode" value="single_user_only" class="form-radio" {{ $registration_mode === 'single_user_only' ? 'checked' : '' }}>
                        <span class="ml-2 text-gray-700">次のアカウントのみを使用する</span>
                    </label>
                    <div id="single_user_did_selector" class="mt-2 ml-6 {{ $registration_mode === 'single_user_only' ? '' : 'hidden' }}">
                        <select name="allowed_single_user_did" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="">選択してください</option>
                            @foreach($all_users as $u)
                                <option value="{{ $u->did }}" {{ $allowed_single_user_did === $u->did ? 'selected' : '' }}>
                                    {{ '@' . $u->handle }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="registration_mode" value="invitation_required" class="form-radio" {{ $registration_mode === 'invitation_required' ? 'checked' : '' }}>
                        <span class="ml-2 text-gray-700">招待コードを使用して登録を許可する</span>
                    </label>
                </div>



                <div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        利用者設定を保存
                    </button>
                </div>
            </form>
        </div>

        <div id="invitation_code_section" class="bg-white shadow-md rounded-lg p-6 mt-6 {{ $registration_mode === 'invitation_required' ? '' : 'hidden' }}">
            <h2 class="text-xl font-semibold mb-4">新しい招待コードを生成</h2>
            <form action="{{ route('settings.generateInvitationCode') }}" method="POST" class="space-y-4">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    コードを生成
                </button>
            </form>
            <div class="mt-8">
                <h5 class="font-semibold mb-4">あなたの招待コード</h5>
                @if($invitation_codes->isEmpty())
                    <p>まだ招待コードを生成していません。</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    コード
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    生成日
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    有効期限
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    使用回数
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    アクション
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invitation_codes as $code)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $code->code }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $code->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $code->expires_at ? $code->expires_at->format('Y-m-d H:i') : 'なし' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $code->current_usage_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-sm font-medium">
                                        <form action="{{ route('settings.deleteInvitationCode', ['invitation_code_id' => $code->id]) }}"
                                              method="POST" onsubmit="return confirm('本当にこの招待コードを削除してもよろしいですか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                削除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg p-6 mt-6">
        <h2 class="text-xl font-bold mb-4 text-red-600">全件再取得</h2>
        <p class="text-sm text-gray-700 mb-4">
            Blueskyの全データを削除・再取得し、Bluelogを最新にリフレッシュします.<br/>
            Bluelog上で削除したポストも、Blueskyに存在していた場合、復活します.<br/>
            <strong class="font-bold text-red-700">この操作は元に戻せません.</strong>
        </p>
        <form action="{{ route('settings.fullSyncData', ['handle' => $profile['handle']]) }}" method="POST"
              onsubmit="return confirm('本当に全件再取得を実行してもよろしいですか？\nこの操作は元に戻せません.データ量によっては時間がかかります.');">
            @csrf
            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                全件再取得を実行
            </button>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6 mt-6">
        <h2 class="text-xl font-bold mb-4 text-red-600">アカウント削除</h2>
        <p class="text-sm text-gray-700 mb-4">
            アカウントを削除すると、あなたのBluelog上の全てのデータ（投稿、いいね、統計など）が完全に削除されます.
        </p>
        <form action="{{ route('settings.destroy') }}" method="POST"
              onsubmit="return confirm('本当にアカウントを削除してもよろしいですか？\nこの操作は元に戻せません.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                アカウントを削除
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const registrationModeRadios = document.querySelectorAll('input[name="registration_mode"]');
        const singleUserDidSelector = document.getElementById('single_user_did_selector');
        const invitationCodeSection = document.getElementById('invitation_code_section');

        function toggleVisibility() {
            const selectedMode = document.querySelector('input[name="registration_mode"]:checked').value;

            singleUserDidSelector.classList.remove('hidden');
            invitationCodeSection.classList.add('hidden');
            if (selectedMode === 'invitation_required') {
                singleUserDidSelector.classList.add('hidden');
                invitationCodeSection.classList.remove('hidden');
            }
        }

        registrationModeRadios.forEach(radio => {
            radio.addEventListener('change', toggleVisibility);
        });

        // 初期表示
        toggleVisibility();
    });
</script>
