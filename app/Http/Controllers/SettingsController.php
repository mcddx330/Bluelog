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

class SettingsController extends Controller {
    use PreparesProfileData, BuildViewBreadcrumbs;

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

        return view(
            'settings.edit', array_merge(
            compact('user', 'breadcrumbs'),
        ), $this->prepareCommonProfileData($user));
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
            'is_private' => 'sometimes|boolean', // 'is_private'が存在する場合、boolean型であることを検証
        ]);

        DB::transaction(function () use ($user, $validated) {
            // バリデートされた値でユーザーの 'is_private' 属性を更新します。
            // リクエストに 'is_private' が含まれていない場合は false をデフォルトとします。
            $user->is_private = $validated['is_private'] ?? false;
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

        if (!$user || $user->handle !== $handle) {
            return back()->with('error', '権限がありません。');
        }

        // status:aggregate コマンドを非同期で実行
        dispatch(function () use ($user) {
            Artisan::call('status:aggregate', [
                '--did'     => $user->did,
                '--full-sync' => true,
                '--force'   => true,
            ]);
        })->onQueue('default');

        return redirect()->route('settings.edit')->with('status', '全件再取得を開始しました。データ量によっては時間がかかります。');
    }
}

