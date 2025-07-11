<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\BuildViewBreadcrumbs;
use App\Traits\PreparesProfileData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Post;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Providers\AppServiceProvider;
use League\Csv\Writer;
use App\Models\InvitationCode;
use Illuminate\Support\Str;
use App\Services\SettingService;

class SettingsController extends Controller {
    use PreparesProfileData, BuildViewBreadcrumbs;

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * ユーザー設定の編集フォームを表示します。
     * 認証済みのユーザー情報を取得し、設定編集ビューに渡します。
     * @return View|RedirectResponse
     */
    public function edit() {
        $user = Auth::user();

        // ユーザーが認証されていない場合
        if (!($user instanceof User)) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        // canShowはSettingsControllerでは不要なので、直接is_privateをチェック
        if ($user->is_private && (!Auth::check() || Auth::user()->did !== $user->did)) {
            // SettingsControllerは認証済みユーザー自身の設定ページなので、
            // 他人の非公開設定ページにアクセスしようとした場合は、ログインページにリダイレクトするか、エラーメッセージを表示する
            // ここでは、ログインページにリダイレクトする例
            return redirect()->route('login')->with('error', 'この設定ページにはアクセスできません。');
        }

        $breadcrumbs = $this
            ->addBreadcrumb('@' . $user->handle, route('profile.show', ['handle' => $user->handle]))
            ->addBreadcrumb('設定')
            ->getBreadcrumbs();

        $invitation_codes = InvitationCode::where('issued_by_user_did', $user->did)->with('usages.user')->get();

        $registration_mode = $this->settingService->get('registration_mode', $this->settingService::KEY_SINGLE_USER_ONLY);
        $allowed_single_user_did = $this->settingService->get('allowed_single_user_did');

        $all_users = User::all();

        return view(
            'settings.edit', array_merge(
            compact('user', 'breadcrumbs', 'invitation_codes', 'registration_mode', 'allowed_single_user_did', 'all_users'),
        ), $this->prepareCommonProfileData($user));
    }

    /**
     * アプリケーションの登録モードを更新します。
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateRegistrationMode(Request $request)
    {
        $user = Auth::user();

        if (!($user instanceof User) || !$user->is_admin) {
            return redirect()->route('login')->with('error', '管理者権限がありません。');
        }

        $validated = $request->validate([
            'registration_mode' => 'required|in:single_user_only,invitation_required',
            'allowed_single_user_did' => 'nullable|exists:users,did',
        ]);

        if ($validated['registration_mode'] === 'single_user_only') {
            $request->validate([
                'allowed_single_user_did' => 'required|exists:users,did',
            ]);
        }

        DB::transaction(function () use ($validated) {
            $this->settingService->set(
                'registration_mode',
                $validated['registration_mode'],
                'string',
                'アプリケーションのユーザー登録モード'
            );

            $this->settingService->set(
                'allowed_single_user_did',
                $validated['allowed_single_user_did'],
                'string',
                'シングルユーザーモードの場合に許可される唯一のユーザーのDID'
            );

            // registration_mode が invitation_required の場合、registration_invitation_code_required を true に設定
            // それ以外の場合は false に設定
            $this->settingService->set(
                'registration_invitation_code_required',
                $validated['registration_mode'] === 'invitation_required',
                'boolean',
                '新規ユーザー登録時に招待コードの入力を必須とするかどうか (registration_modeに統合)'
            );
        });

        return back()->with('status', '利用者設定を更新しました。');
    }

    /**
     * ユーザー設定をストレージに更新します。
     * リクエストからバリデートされた設定値（例: is_private）を取得し、
     * 認証済みユーザーのモデルを更新して保存します。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     *
     * @return RedirectResponse 設定更新後のリダイレクトレスポポンス。
     */
    public function update(Request $request) {
        $user = Auth::user();

        // ユーザーが認証されていない場合
        if (!($user instanceof User)) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        // リクエストデータのバリデーションを行います。
        $validated = $request->validate([
            'is_private' => 'sometimes|boolean',
            'invisible_badge' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->is_private = $validated['is_private'] ?? false;
            $user->invisible_badge = $validated['invisible_badge'] ?? false;
            $user->save();
        });

        // 設定更新成功のステータスメッセージと共に前のページにリダイレクトします。
        return back()->with('status', '設定を更新しました。');
    }

    /**
     * ユーザーアカウントを削除します。
     * 認証済みのユーザーアカウントをデータベースから削除し、ログアウトさせます。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     *
     * @return RedirectResponse 削除後のリダイレクトレスポンス。
     */
    public function destroy(Request $request) {
        $user = Auth::user();

        // ユーザーが認証されていない場合
        if (!($user instanceof User)) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        DB::transaction(function () use ($user) {
            $user->delete(); // ユーザーと関連データを削除
        });

        // ログアウト処理
        $blueskyController = new BlueskyController();
        $blueskyController->doLogout($request);

        return redirect('/')->with('status', 'アカウントが削除されました。');
    }

    /**
     * 認証済みユーザーの投稿データをCSV形式でエクスポートします。
     * @return RedirectResponse|StreamedResponse
     */
    public function exportPosts() {
        $user = Auth::user();

        // ユーザーが認証されていない場合
        if (!($user instanceof User)) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        $filename = sprintf(
            'bluelog_posts_%s_%s.csv',
            $user->handle,
            now()->format('Ymd_His')
        );


        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($user) {
            $csv = Writer::createFromPath('php://output', 'w');
            $csv->setOutputBOM(Writer::BOM_UTF8);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');

            // ヘッダー行
            $csv->insertOne(['CID', 'URL', '投稿日時', '本文']);

            // 投稿データをチャンクで取得し、処理
            Post::where('did', $user->did)
                ->orderBy('posted_at', 'desc')
                ->chunk(1000, function ($posts) use ($csv) {
                    foreach ($posts as $post) {
                        $bluesky_url = 'https://bsky.app/profile/' . $post->did . '/' . $post->rkey;
                        $text = AppServiceProvider::renderBlueskyText($post->text);
                        // HTMLタグを除去
                        $text = strip_tags($text);
                        // 改行コードを統一
                        $text = str_replace(["\r\n", "\r"], "\n", $text);

                        $csv->insertOne([
                            $post->cid,
                            $bluesky_url,
                            $post->posted_at->format('Y-m-d H:i:s'),
                            $text,
                        ]);
                    }
                });
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 認証済みユーザーのBlueskyデータを全件再取得します。
     *
     * @param string $handle 更新するユーザーのハンドル名。
     *
     * @return RedirectResponse プロフィール表示ページへのリダイレクトレスポンス。
     */
    public function fullSyncData(string $handle) {
        $user = Auth::user();

        // ユーザーが認証されていない場合
        if (!($user instanceof User) || $user->handle !== $handle) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        // status:aggregate コマンドを非同期で実行
        dispatch(function () use ($user) {
            Artisan::call('status:aggregate', [
                '--did'       => $user->did,
                '--full-sync' => true,
                '--force'     => true,
            ]);
        })->onQueue('default');

        return redirect()->route('settings.edit')->with('status', '全件再取得を開始しました。データ量によっては時間がかかります。');
    }

    /**
     * 新しい招待コードを生成します。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     *
     * @return RedirectResponse 招待コード生成後のリダイレクトレスポンス。
     */
    public function generateInvitationCode(Request $request) {
        $user = Auth::user();

        // ユーザーが認証されていない場合
        if (!($user instanceof User)) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        // 既存のアクティブな招待コードを非アクティブ化
        InvitationCode::where('issued_by_user_did', $user->did)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        $invitation_code = InvitationCode::create([
            'code'               => Str::random(16), // 16文字のランダムなコード
            'issued_by_user_did' => $user->did,
            'usage_limit'        => null, // 利用回数制限なし
            'expires_at'         => now()->addMonth()->endOfDay(), // 1ヶ月後に有効期限切れ
            'status'             => 'active',
        ]);

        return back()->with('success', '招待コードが生成されました: ' . $invitation_code->code);
    }

    /**
     * 指定された招待コードを削除します。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     * @param string $invitation_code_id 削除する招待コードのID。
     * @return RedirectResponse 招待コード削除後のリダイレクトレスポンス。
     */
    public function deleteInvitationCode(Request $request, string $invitation_code_id)
    {
        $user = Auth::user();

        // ユーザーが認証されていない場合
        if (!($user instanceof User)) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        $invitation_code = InvitationCode::where('id', $invitation_code_id)
            ->where('issued_by_user_did', $user->did)
            ->first();

        if (!$invitation_code) {
            return back()->with('error', '指定された招待コードが見つからないか、削除する権限がありません。');
        }

        try {
            $invitation_code->delete();
            return back()->with('success', '招待コードが正常に削除されました。');
        } catch (\Exception $e) {
            \Log::error(sprintf(
                '招待コード削除中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));
            return back()->with('error', '招待コードの削除中にエラーが発生しました。');
        }
    }
}

