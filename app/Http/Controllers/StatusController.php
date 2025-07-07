<?php

namespace App\Http\Controllers;

use App\Models\DailyStat;
use App\Models\User;
use App\Traits\BuildViewBreadcrumbs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\PreparesProfileData;

class StatusController extends Controller {
    use PreparesProfileData, BuildViewBreadcrumbs;

    /**
     * 指定されたハンドルのユーザーの統計情報を表示します。
     * データベースから日ごとの統計データを取得し、集計してビューに渡します。
     *
     * @param string                   $handle 表示するユーザーのハンドル名。
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。
     *
     * @return \Illuminate\Contracts\View\View 統計情報ビュー。
     */
    public function show(string $handle, Request $request) {
        // ハンドル名に基づいてユーザーをデータベースから検索します。見つからない場合は例外をスローします。
        $user = User::where('handle', $handle)->firstOrFail();

        // プロフィールが非公開設定の場合のチェックを行います。
        if ($user->is_private) {
            // ログインしているユーザーが、表示対象のユーザー本人でない場合は403エラーを返します。
            if (!Auth::check() || Auth::user()->did !== $user->did) {
                return response('このプロフィールは非公開です。', 403);
            }
        }

        // ユーザーのDIDに基づいて日ごとの統計データを取得し、日付の昇順で並べ替えます。
        // dateフィールドは自動的にCarbonインスタンスに変換され、時刻は00:00:00に設定されます。
        $stats = DailyStat::where('did', $user->did)
            ->orderBy('date', 'asc') // 期間計算のために昇順に
            ->get()
            ->map(function ($stat) {
                $stat->date = \Carbon\Carbon::parse($stat->date)->startOfDay();

                return $stat;
            });

        // 各種合計値の計算
        $total_posts = $stats->sum('posts_count');
        $total_likes = $stats->sum('likes_count');
        $total_replies = $stats->sum('replies_count');
        $total_reposts = $stats->sum('reposts_count');

        // 投稿があった日数の計算
        $days_with_posts = $stats->where('posts_count', '>', 0)->count();
        $max_posts_per_day = 0;
        $max_posts_per_day_date = null;

        // 最大投稿数の日と日付を特定
        if ($stats->isNotEmpty()) {
            $max_posts_per_day = $stats->max('posts_count');
            // max()が0を返す可能性があるので、その場合はfirst()を呼ばないようにする
            if ($max_posts_per_day > 0) {
                $max_posts_per_day_date = $stats
                    ->where('posts_count', $max_posts_per_day)
                    ->sortByDesc('date')
                    ->first()
                    ->date
                    ->format('Y-m-d');
            }
        }

        // 統計期間の開始日と終了日を特定
        $period_start = $stats->min('date');
        $period_end = $stats->max('date');

        // 統計期間の日数と投稿がなかった日数を計算
        $period_days = 0;
        $days_without_posts = 0;
        if ($period_start && $period_end) {
            $period_days = $period_start->diffInDays($period_end) + 1;
            $days_without_posts = $period_days - $days_with_posts;
        }

        // 1日あたりの平均投稿数を計算
        $average_posts_per_day = $days_with_posts > 0 ? round($total_posts / $days_with_posts, 2) : 0;

        // ツイート文字数関連
        $total_text_length = 0;
        $posts_with_text_count = 0;
        $user_posts = \App\Models\Post::where('did', $user->did)->get();

        foreach ($user_posts as $post) {
            $cleaned_text = $post->text;
            // URLを除外
            $cleaned_text = preg_replace('/https?:\/\/\S+/', '', $cleaned_text);
            // @ユーザー名を除外 (Blueskyハンドル形式に対応)
            $cleaned_text = preg_replace('/@[\p{L}\p{N}.-]+\.[\p{L}]{2,}/u', '', $cleaned_text);
            // #ハッシュタグを除外
            $cleaned_text = preg_replace('/#([\p{L}\p{N}_]+)/u', '', $cleaned_text);
            // スペースと改行を除外
            $cleaned_text = preg_replace('/\s+/', '', $cleaned_text);

            $text_length = mb_strlen($cleaned_text);
            $total_text_length += $text_length;
            if ($text_length > 0) {
                $posts_with_text_count++;
            }
        }

        $average_text_per_post = $posts_with_text_count > 0 ? round($total_text_length / $posts_with_text_count, 2) : 0;
        $average_text_per_day = $period_days > 0 ? round($total_text_length / $period_days, 2) : 0;

        // フォロー/フォロワー増加ペース
        $days_since_registered = $user->registered_at ? $user->registered_at->diffInDays(now()) : 0;
        $follower_growth_pace = $days_since_registered > 0 ? round($user->followers_count / $days_since_registered, 2) : 0;
        $following_growth_pace = $days_since_registered > 0 ? round($user->following_count / $days_since_registered, 2) : 0;

        // コミュニケーション率
        $replied_posts_count = \App\Models\Post::where('did', $user->did)
            ->whereNotNull('reply_to_handle')
            ->count();
        $communication_rate = $user->posts_count > 0 ? round(($replied_posts_count / $user->posts_count) * 100, 2) : 0;

        // フォロワー対フォロー比率を計算
        $follower_following_ratio = 0;
        if ($user->following_count > 0) {
            $follower_following_ratio = round($user->followers_count / $user->following_count, 2);
        }

        // グラフ描画用のデータを準備するクロージャ
        $prepare_chart_data = function ($collection) {
            return collect([
                'labels'  => $collection->pluck('date')->map(fn($date) => $date->toDateString()),
                'posts'   => $collection->pluck('posts_count'),
                'likes'   => $collection->pluck('likes_count'),
                'replies' => $collection->pluck('replies_count'),
                'reposts' => $collection->pluck('reposts_count'),
            ]);
        };

        // 全期間、過去30日、60日、90日のグラフデータを準備
        $all_stats = $stats; // 全期間のデータ
        $last_30_days_stats = $stats->slice(max(0, $stats->count() - 30));
        $last_60_days_stats = $stats->slice(max(0, $stats->count() - 60));
        $last_90_days_stats = $stats->slice(max(0, $stats->count() - 90));

        $chart_data_all = $prepare_chart_data($all_stats);
        $chart_data_30 = $prepare_chart_data($last_30_days_stats);
        $chart_data_60 = $prepare_chart_data($last_60_days_stats);
        $chart_data_90 = $prepare_chart_data($last_90_days_stats);

        // 統計情報ビューにデータを渡して表示します。
        try {
            return view('status.show', array_merge([
                'breadcrumbs'              => $this
                    ->addBreadcrumb($user->handle, route('profile.show', ['handle' => $user->handle]))
                    ->addBreadcrumb('ステータス')
                    ->getBreadcrumbs(),
                'stats'                    => $stats,
                'total_posts'              => $total_posts,
                'total_likes'              => $total_likes,
                'total_replies'            => $total_replies,
                'total_reposts'            => $total_reposts,
                'days_with_posts'          => $days_with_posts,
                'days_without_posts'       => $days_without_posts,
                'max_posts_per_day'        => $max_posts_per_day,
                'average_posts_per_day'    => $average_posts_per_day,
                'period_start'             => $period_start,
                'period_end'               => $period_end,
                'period_days'              => $period_days,
                'follower_following_ratio' => $follower_following_ratio,
                'max_posts_per_day_date'   => $max_posts_per_day_date,
                'total_text_length'        => $total_text_length,
                'average_text_per_post'    => $average_text_per_post,
                'average_text_per_day'     => $average_text_per_day,
                'follower_growth_pace'     => $follower_growth_pace,
                'following_growth_pace'    => $following_growth_pace,
                'communication_rate'       => $communication_rate,
                'chart_data_all'           => $chart_data_all,
                'chart_data_30'            => $chart_data_30,
                'chart_data_60'            => $chart_data_60,
                'chart_data_90'            => $chart_data_90,
            ], $this->prepareCommonProfileData($user)));
        } catch (\JsonException $e) {
            Log::error(sprintf(
                '統計情報表示中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));

            return back()->with('error', '統計情報表示中にエラーが発生しました。詳細についてはログを確認してください。');
        }
    }
}
