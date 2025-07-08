<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\BuildViewBreadcrumbs;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\PreparesProfileData;

class BlueskyArchivesController extends BlueskyController {
    use PreparesProfileData, BuildViewBreadcrumbs;

    /**
     * 指定されたハンドルのユーザーのアーカイブ履歴を表示します。
     *
     * @param string $handle 表示するユーザーのハンドル名。
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|object
     */
    public function show(string $handle) {
        // Blueskyセッションが存在する場合、セッションを再開します。
        if ($this->getBlueskySession()) {
            $this->resumeSession();
        }

        try {
            // ハンドル名に基づいてユーザーをデータベースから検索します。見つからない場合は例外をスローします。
            $user = User::where('handle', $handle)->first();

            // ユーザーが存在しない場合
            if (!($user instanceof User)) {
                return redirect()->route('profile.show', ['handle' => $handle]);
            }

            // ユーザーが存在し、表示可能かどうかを判定
            if (!$user->canShow()) {
                return redirect()->route('profile.show', ['handle' => $handle]);
            }

            // アーカイブ履歴ビューにデータを渡して表示します。
            return view('archives', array_merge([
                'breadcrumbs' => $this
                    ->addBreadcrumb($user->handle, route('profile.show', ['handle' => $user->handle]))
                    ->addBreadcrumb('アーカイブ')
                    ->getBreadcrumbs(),
                'handle'      => $handle,
            ], $this->prepareCommonProfileData($user)));
        } catch (Exception $e) {
            Log::error(sprintf(
                'アーカイブ表示中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));

            return back()->with('error', 'アーカイブ表示中にエラーが発生しました。詳細についてはログを確認してください。');
        }
    }
}
