<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\BuildViewBreadcrumbs;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\PreparesProfileData;

class BlueskyLikesController extends BlueskyController {
    use PreparesProfileData, BuildViewBreadcrumbs;

    /**
     * 指定されたハンドルのユーザーの「いいね」履歴を表示します。
     * データベースから「いいね」データを取得し、ビューに渡します。
     *
     * @param string $handle 表示するユーザーのハンドル名。
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse 「いいね」履歴ビューまたはリダイレクトレスポンス。
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

            $did = $user->did;

            // データベースから指定されたDIDのユーザーの「いいね」レコードを取得します。
            // 新しいものから順に20件ずつページネーションします。
            $likes = \App\Models\Like::where('did', $did)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $posts = [];
            // 取得した「いいね」データから、ビューに渡すための投稿情報を整形します。
            foreach ($likes as $like) {
                $posts[] = [
                    'bluesky_uri' => $like->post_uri, // Blueskyの投稿URI (at:// 形式)
                    'bluesky_cid' => $like->cid,      // Blueskyの投稿CID
                    'liked_at'    => $like->created_at, // いいねした日時
                ];
            }

            // 「いいね」履歴ビューにデータを渡して表示します。
            return view('likes', array_merge([
                'handle'           => $handle,
                'posts'            => $posts,
                'likes_pagination' => $likes, // ページネーション情報をビューに渡します。
                'breadcrumbs'      => $this
                    ->addBreadcrumb($user->handle, route('profile.show', ['handle' => $user->handle]))
                    ->addBreadcrumb('いいね')
                    ->getBreadcrumbs(),
            ], $this->prepareCommonProfileData($user)));
        } catch (Exception $e) {
            Log::error(sprintf(
                'いいね表示中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));

            return back()->with('error', 'いいね表示中にエラーが発生しました。詳細についてはログを確認してください。');
        }
    }
}
