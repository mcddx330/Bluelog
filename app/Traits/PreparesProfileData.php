<?php

namespace App\Traits;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait PreparesProfileData {
    /**
     * ユーザーモデルからプロフィールデータとis_fetchingフラグを準備します。
     *
     * @param User $user
     *
     * @return array
     */
    protected function prepareCommonProfileData(User $user): array {
        $profile_data = [
            'did'             => $user->did,
            'handle'          => $user->handle,
            'display_name'    => $user->display_name,
            'description'     => $user->description,
            'avatar'          => $user->avatar_url,
            'banner'          => $user->banner_url,
            'followers_count' => $user->followers_count,
            'follows_count'   => $user->following_count,
            'posts_count'     => $user->posts_count,
            'likes_count'     => $user->likes->count(), // likes_countはリレーションから取得
            'created_at'      => $user->registered_at ? $user->registered_at->toIso8601String() : null,
        ];

        $is_fetching = $user->isFetchingData();

        // メンションランキング上位10名を取得
        $top_mentions = Post::where('did', $user->did)
            ->whereNotNull('reply_to_handle')
            ->select('reply_to_handle', DB::raw('count(*) as mention_count'))
            ->groupBy('reply_to_handle')
            ->orderBy('mention_count', 'desc')
            ->orderBy('reply_to_handle', 'asc')
            ->limit(10)
            ->get();

        // ハッシュタグランキング上位10件を取得
        $top_hashtags = \App\Models\Hashtag::whereHas('post', function ($query) use ($user) {
            $query->where('did', $user->did);
        })
            ->select('tag', DB::raw('count(*) as count'))
            ->groupBy('tag')
            ->orderBy('count', 'desc')
            ->orderBy('tag', 'asc')
            ->limit(10)
            ->get();

        // 過去1年間のDailyStatデータを取得
        $dailyStats = \App\Models\DailyStat::where('did', $user->did)
            ->where('date', '>=', now()->subYear())
            ->orderBy('date', 'asc')
            ->get()
            ->mapWithKeys(function ($stat) {
                return [$stat->date_carbon->format('Y-m-d') => $stat->posts_count];
            });

        // アーカイブリストを取得
        $archives = Post::where('did', $user->did)
            ->selectRaw('strftime("%Y%m", posted_at) as year_month, COUNT(*) as count')
            ->groupBy('year_month')
            ->orderBy('year_month', 'desc')
            ->get()
            ->map(function ($archive) {
                return [
                    'ym'    => $archive->year_month,
                    'label' => substr($archive->year_month, 0, 4) . '年' . substr($archive->year_month, 4, 2) . '月',
                    'count' => $archive->count,
                ];
            });

        return [
            'is_fetching'  => $is_fetching,
            'user'         => $user,
            'did'          => $profile_data['did'],
            'handle'       => $profile_data['handle'],
            'profile'      => $profile_data,
            'top_mentions' => $top_mentions,
            'top_hashtags' => $top_hashtags,
            'daily_stats'  => $dailyStats,
            'archives'     => $archives,
        ];
    }
}
