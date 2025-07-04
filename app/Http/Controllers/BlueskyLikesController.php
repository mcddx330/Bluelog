<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Revolution\Bluesky\Facades\Bluesky;
use App\Traits\PreparesProfileData;

class BlueskyLikesController extends BlueskyController
{
    use PreparesProfileData;
    /**
     * 指定されたハンドルのユーザーの「いいね」履歴を表示します。
     * データベースから「いいね」データを取得し、ビューに渡します。
     *
     * @param  string  $handle 表示するユーザーのハンドル名。
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse 「いいね」履歴ビューまたはリダイレクトレスポンス。
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
                'handle' => $handle,
                'posts' => $posts,
                'likes_pagination' => $likes, // ページネーション情報をビューに渡します。
            ], $this->prepareCommonProfileData($user)));
        } catch (Exception $e) {
            // エラーが発生した場合は、エラーメッセージと共に前のページに戻ります。
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
