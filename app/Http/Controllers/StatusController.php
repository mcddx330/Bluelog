<?php

namespace App\Http\Controllers;

use App\Models\DailyStat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\PreparesProfileData;

class StatusController extends Controller
{
    use PreparesProfileData;
    /**
     * 指定されたハンドルのユーザーの統計情報を表示します。
     * データベースから日ごとの統計データを取得し、集計してビューに渡します。
     *
     * @param  string  $handle 表示するユーザーのハンドル名。
     * @param  \Illuminate\Http\Request  $request HTTPリクエストオブジェクト。
     * @return \Illuminate\Contracts\View\View 統計情報ビュー。
     */
    public function show(string $handle, Request $request)
    {
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
        $total_replies = $stats->sum('replies_count');

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

        // フォロワー対フォロー比率を計算
        $follower_following_ratio = 0;
        if ($user->following_count > 0) {
            $follower_following_ratio = round($user->followers_count / $user->following_count, 2);
        }

        // グラフ描画用のデータを準備するクロージャ
        $prepare_chart_data = function($collection) {
            return [
                'labels' => $collection->pluck('date')->map(fn($date) => $date->toDateString())->toArray(),
                'posts' => $collection->pluck('posts_count')->toArray(),
                'likes' => $collection->pluck('likes_count')->toArray(),
                'replies' => $collection->pluck('replies_count')->toArray(),
                'reposts' => $collection->pluck('reposts_count')->toArray(),
                'replies' => $collection->pluck('replies_count')->toArray(),
            ];
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
                'stats'                    => $stats,
                'total_posts'              => $total_posts,
                'total_likes'              => $total_likes,
                'total_replies'            => $total_replies,
                'total_reposts'            => $total_reposts,
                'total_replies'           => $total_replies,
                'days_with_posts'          => $days_with_posts,
                'days_without_posts'       => $days_without_posts,
                'max_posts_per_day'        => $max_posts_per_day,
                'average_posts_per_day'    => $average_posts_per_day,
                'period_start'             => $period_start,
                'period_end'               => $period_end,
                'period_days'              => $period_days,
                'follower_following_ratio' => $follower_following_ratio,
                'max_posts_per_day_date'   => $max_posts_per_day_date, // ここを追加
                'chart_data_all'           => json_encode($chart_data_all, JSON_THROW_ON_ERROR),
                'chart_data_30'            => json_encode($chart_data_30, JSON_THROW_ON_ERROR),
                'chart_data_60'            => json_encode($chart_data_60, JSON_THROW_ON_ERROR),
                'chart_data_90'            => json_encode($chart_data_90, JSON_THROW_ON_ERROR),
            ], $this->prepareCommonProfileData($user)));
        } catch (\JsonException $e) {
            dd($e->getFile(), $e->getLine(), $e->getMessage(), $e->getTrace());
        }
    }
}
