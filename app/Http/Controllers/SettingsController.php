<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\BlueskyController;

class SettingsController extends Controller
{
    /**
     * ユーザー設定の編集フォームを表示します。
     *
     * 認証済みのユーザー情報を取得し、設定編集ビューに渡します。
     *
     * @return \Illuminate\Contracts\View\View 設定編集ビュー。
     */
    public function edit()
    {
        $user = Auth::user();

        return view('settings.edit', compact('user'));
    }

    /**
     * ユーザー設定をストレージに更新します。
     *
     * リクエストからバリデートされた設定値（例: is_private）を取得し、
     * 認証済みユーザーのモデルを更新して保存します。
     *
     * @param  \Illuminate\Http\Request  $request HTTPリクエストオブジェクト。
     * @return \Illuminate\Http\RedirectResponse 設定更新後のリダイレクトレスポンス。
     */
    public function update(Request $request)
    {
        $user = Auth::user();

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
     *
     * 認証済みのユーザーアカウントをデータベースから削除し、ログアウトさせます。
     *
     * @param  \Illuminate\Http\Request  $request HTTPリクエストオブジェクト。
     * @return \Illuminate\Http\RedirectResponse 削除後のリダイレクトレスポンス。
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();

        DB::transaction(function () use ($user) {
            $user->delete(); // ユーザーと関連データを削除
        });

        // ログアウト処理
        $blueskyController = new BlueskyController();
        $blueskyController->doLogout($request);

        return redirect('/')->with('status', 'アカウントが削除されました。');
    }
}
