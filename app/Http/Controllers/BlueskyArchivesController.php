<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\PreparesProfileData;

class BlueskyArchivesController extends BlueskyController
{
    use PreparesProfileData;

    /**
     * 指定されたハンドルのユーザーのアーカイブ履歴を表示します。
     *
     * @param  string  $handle 表示するユーザーのハンドル名。
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse アーカイブ履歴ビューまたはリダイレクトレスポンス。
     */
    public function show(string $handle)
    {
        // Blueskyセッションが存在する場合、セッションを再開します。
        if ($this->getBlueskySession()) {
            $this->resumeSession();
        }

        try {
            // ハンドル名に基づいてユーザーをデータベースから検索します。見つからない場合は例外をスローします。
            $user = User::where('handle', $handle)->firstOrFail();

            // プロフィールが非公開設定の場合のチェックを行います。
            if ($user->is_private) {
                // ログインしているユーザーが、表示対象のユーザー本人でない場合は403エラーを返します。
                if (!Auth::check() || Auth::user()->did !== $user->did) {
                    return response('このプロフィールは非公開です。', 403);
                }
            }

            // アーカイブ履歴ビューにデータを渡して表示します。
            return view('archives', array_merge([
                'handle' => $handle,
            ], $this->prepareCommonProfileData($user)));
        } catch (Exception $e) {
            // エラーが発生した場合は、エラーメッセージと共に前のページに戻ります。
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
