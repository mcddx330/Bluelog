<?php

namespace App\Console\Commands;

use App\Models\DailyStat;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Notifications\AggregateStatusFailedNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Revolution\Bluesky\BlueskyManager;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\LegacySession;
use App\Models\Media;

class AggregateStatusCommand extends Command {
    protected $signature = 'status:aggregate {--did= : 集計対象ユーザーのDID (オプション)} {--full-sync : 既存のカーソルを無視して、強制的に全件同期を実行します} {--force : --full-sync 使用時に確認プロンプトを表示しません}';

    protected $description = 'Blueskyユーザーのアクティビティを取得し、集計します。--full-sync オプションを使用すると、既存のデータを削除し、Blueskyから全件再取得します。';

    public function handle(): void {
        $this->info('Blueskyステータス集計を開始します...');

        $full_sync = $this->option('full-sync');
        $force = $this->option('force');

        if ($full_sync && !$force) {
            $this->warn('--full-sync オプションが指定されました。これにより、既存の投稿、いいね、メディア、ハッシュタグ、日別統計データが削除され、Blueskyから全件再取得されます。');
            if ($this->confirm('続行しますか？ (yes/no)') === false) {
                $this->info('処理を中断しました。');
                return;
            }
        }
        $did = $this->option('did');

        /** @var \Illuminate\Database\Eloquent\Builder $users クエリビルダー */
        $users = User::where('did', $did);
        if (empty($did)) {
            $users = User::query()
                ->where('is_fetching', false)
                ->orWhereNull('is_fetching');
        }

        /** @var \Illuminate\Database\Eloquent\Collection<User> $users ユーザーのコレクション */
        $users = $users->get();

        if ($users->isEmpty()) {
            $this->comment('処理対象のユーザーがありません。');

            return;
        }

        $this->info(sprintf('%d 人のユーザーを処理します。', $users->count()));

        foreach ($users as $user) {
            $this->comment(sprintf('==== ユーザー処理開始: %s ====', $user->handle));

            try {
                $bluesky = $this->prepareBlueskyClient($user);

                $this->info(sprintf('[Client] 準備完了: %s', $user->handle));

                DB::transaction(function () use ($user) {
                    $user->markFetching();
                });

                DB::transaction(function () use ($bluesky, $user, $full_sync) {
                    // プロフィール更新
                    $this->updateUserProfile($bluesky, $user);

                    if ($full_sync) {
                        $this->info(sprintf('[full-sync] 既存の投稿データを削除します: %s', $user->handle));
                        $user->posts()->delete();
                        $this->info(sprintf('[full-sync] 既存のいいねデータを削除します: %s', $user->handle));
                        $user->likes()->delete();
                        $this->info(sprintf('[full-sync] 既存の日別統計データを削除します: %s', $user->handle));
                        $user->dailyStats()->delete();
                    }

                    // 投稿といいねの取得・保存
                    $post_daily_data = $this->fetchAndStorePosts($bluesky, $user, $full_sync);
                    $like_daily_data = $this->fetchAndStoreLikes($bluesky, $user, $full_sync);

                    // 日別統計を統合して保存
                    $this->upsertDailyStats($user, $post_daily_data, $like_daily_data);

                });

                DB::transaction(function () use ($user) {
                    $user->unmarkFetching();
                });

                $this->comment(sprintf('==== ユーザー処理終了: %s ====', $user->handle));
            } catch (\Exception $e) {
                \Log::error(sprintf(
                    'ユーザー %s の集計中にエラー: %s %d . %s',
                    $user->handle,
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                ));

                // ユーザーに通知を記録
                $user->notify(new AggregateStatusFailedNotification(
                    'データ集計時ににエラーが発生しました。設定より再取得を行ってください。'
                ));
            } finally {
                // フラグ解除と最終取得時刻更新を保証
                $user->is_fetching = false;
                $user->last_fetched_at = now();
                $user->save();
                $this->comment(sprintf('最終取得時刻を更新: %s', $user->last_fetched_at));
            }
        }

        $this->info('全ユーザーの集計処理が完了しました。');
    }

    private function prepareBlueskyClient(User $user): BlueskyManager {
        $this->comment(sprintf('[prepare] セッションデータを作成: %s', $user->handle));
        $session_data = [
            'accessJwt'  => $user->access_jwt,
            'refreshJwt' => $user->refresh_jwt,
            'did'        => $user->did,
            'handle'     => $user->handle,
        ];
        $bluesky = Bluesky::withToken(LegacySession::create($session_data));

        try {
            $this->comment('[prepare] トークンチェック実施');
            $bluesky->check();
        } catch (\Throwable) {
            $this->comment(sprintf('アクセストークン期限切れ: %s → リフレッシュ実施', $user->handle));
            $response = $bluesky->refreshSession();
            $this->info('[prepare] トークンリフレッシュ完了');

            $user->access_jwt = $response->access_jwt;
            $user->refresh_jwt = $response->refresh_jwt;
            $user->save();
            $this->comment('[prepare] 新しいトークンをユーザーに保存');

            // リフレッシュしたトークンでクライアントを再生成
            $bluesky = Bluesky::withToken(LegacySession::create([
                'accessJwt'  => $user->access_jwt,
                'refreshJwt' => $user->refresh_jwt,
                'did'        => $user->did,
                'handle'     => $user->handle,
            ]));
        }

        return $bluesky;
    }

    private function updateUserProfile(BlueskyManager $bluesky, User $user): void {
        $this->comment(sprintf('[profile] %s のプロフィール取得中...', $user->handle));
        $profile_response = $bluesky->getProfile($user->handle);
        $profile_data = json_decode($profile_response->getBody(), true);

        $user->display_name = $profile_data['displayName'] ?? $user->display_name;
        $user->description = $profile_data['description'] ?? $user->description;
        $user->avatar_url = $profile_data['avatar'] ?? $user->avatar_url;
        $user->banner_url = $profile_data['banner'] ?? $user->banner_url;
        $user->save();
        $this->info(sprintf('[profile] %s のプロフィールを更新しました。', $user->handle));
    }

    private function fetchAndStorePosts(BlueskyManager $bluesky, User $user, bool $full_sync = false): array {
        $this->comment(sprintf('[posts] %s の投稿取得開始...', $user->handle));
        $daily_data = [];
        $newest_post_cid = null;
        $last_synced_post_cid = $full_sync ? null : $user->last_synced_post_cid;
        $cursor = null;
        $new_count = 0;

        do {
            $response = $bluesky->getAuthorFeed(actor: $user->handle, limit: 100, cursor: $cursor);
            $body = json_decode($response->getBody(), true);
            $cursor = $body['cursor'] ?? null;
            $feeds = $body['feed'] ?? [];

            if (empty($feeds)) {
                break; // 取得する投稿がない場合
            }

            foreach ($feeds as $item) {
                $post_cid = $item['post']['cid'];

                // 初回ループで最新のCIDを記録
                if ($newest_post_cid === null) {
                    $newest_post_cid = $post_cid;
                }

                // 既に同期済みの投稿に到達した場合、処理を打ち切る
                if ($last_synced_post_cid && $post_cid === $last_synced_post_cid) {
                    $this->info("投稿: 既に同期済みのCID {$post_cid} に到達しました。");
                    $cursor = null; // ループを終了させる
                    break;
                }

                $post_uri = $item['post']['uri'];
                $created_at = Carbon::parse($item['post']['record']['createdAt'])->setTimezone(config('app.timezone'));
                $indexed_at = Carbon::parse($item['post']['indexedAt'])->setTimezone(config('app.timezone'));
                $reply_to_handle = null;
                if (isset($item['post']['record']['reply']['root']['uri'])) {
                    $reply_to_uri = $item['post']['record']['reply']['root']['uri'];
                    // URIからDIDを抽出
                    $parts = explode('/', $reply_to_uri);
                    if (isset($parts[2])) {
                        $reply_to_did = $parts[2];
                        // Bluesky APIからDIDに対応するハンドルを取得
                        try {
                            $profile_response = $bluesky->getProfile($reply_to_did);
                            $profile_body = json_decode($profile_response->getBody(), true);
                            $reply_to_handle = $profile_body['handle'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning(sprintf('Failed to fetch handle for DID %s: %s', $reply_to_did, $e->getMessage()));
                        }
                    }
                }

                $post = Post::updateOrCreate(
                    ['cid' => $post_cid, 'did' => $user->did],
                    [
                        'uri'             => $post_uri,
                        'rkey'            => basename($post_uri),
                        'text'            => $item['post']['record']['text'] ?? '',
                        'reply_to'        => $item['post']['record']['reply']['root']['uri'] ?? null,
                        'reply_to_handle' => $reply_to_handle,
                        'quote_of'        => $item['post']['record']['embed']['record']['uri'] ?? null,
                        'is_repost'       => $item['post']['record']['$type'] === 'app.bsky.feed.repost',
                        'likes_count'     => $item['post']['likeCount'] ?? 0,
                        'replies_count'   => $item['post']['replyCount'] ?? 0,
                        'reposts_count'   => $item['post']['repostCount'] ?? 0,
                        'posted_at'       => $created_at,
                        'indexed_at'      => $indexed_at,
                    ]
                );

                // メディアの保存
                if (isset($item['post']['embed'])) {
                    $this->storeMedia(
                        post: $post,
                        item: $item['post']
                    );
                }

                // ハッシュタグの保存
                if (isset($item['post']['record']['facets'])) {
                    foreach ($item['post']['record']['facets'] as $facet) {
                        if (isset($facet['features'])) {
                            foreach ($facet['features'] as $feature) {
                                if ($feature['$type'] === 'app.bsky.richtext.facet#tag') {
                                    $tag =
                                        substr($item['post']['record']['text'], $facet['index']['byteStart'], $facet['index']['byteEnd'] -
                                                                                                              $facet['index']['byteStart']);
                                    // #を削除して保存
                                    \App\Models\Hashtag::updateOrCreate(
                                        ['post_id' => $post->id, 'tag' => ltrim($tag, '#')],
                                        []
                                    );
                                }
                            }
                        }
                    }
                }
                $date = $created_at->toDateString();
                if (!isset($daily_data[$date])) {
                    $daily_data[$date] = [
                        'posts_count'   => 0,
                        'replies_count' => 0,
                        'reposts_count' => 0,
                    ];
                }
                $daily_data[$date]['posts_count'] += 1;

                // リプライのカウント
                if (isset($item['post']['record']['reply'])) {
                    $daily_data[$date]['replies_count'] = ($daily_data[$date]['replies_count'] ?? 0) + 1;
                }

                // リポストのカウント
                if ($item['post']['record']['$type'] === 'app.bsky.feed.repost') {
                    $daily_data[$date]['reposts_count'] = ($daily_data[$date]['reposts_count'] ?? 0) + 1;
                }

                $new_count++;
            }

            $this->info(sprintf('[posts] ページ取得完了 (新規%d件) cursor=%s', $new_count, $cursor));
        } while ($cursor);

        // last_synced_post_cid の更新
        if ($newest_post_cid) {
            $user->last_synced_post_cid = $newest_post_cid;
            $user->save();
        }

        $total_posts = collect($daily_data)->sum('posts_count');
        $total_replies = collect($daily_data)->sum('replies_count');
        $total_reposts = collect($daily_data)->sum('reposts_count');

        $this->info(sprintf('[posts] 全体で新規投稿 %d件, リプライ %d件, リポスト %d件 を登録しました。', $total_posts, $total_replies, $total_reposts));

        return $daily_data;
    }

    private function fetchAndStoreLikes(BlueskyManager $bluesky, User $user, bool $full_sync = false): array {
        $this->comment(sprintf('[likes] %s のいいね取得開始...', $user->handle));
        $daily_data = [];
        $newest_like_cid = null;
        $last_synced_like_cid = $full_sync ? null : $user->last_synced_like_cid;
        $cursor = null;
        $new_count = 0;

        do {
            $response = $bluesky->listRecords(
                repo: $user->did,
                collection: 'app.bsky.feed.like',
                limit: 100,
                cursor: $cursor,
            );
            $body = json_decode($response->getBody(), true);
            $cursor = $body['cursor'] ?? null;
            $likes = $body['records'] ?? [];

            if (empty($likes)) {
                break; // 取得するいいねがない場合
            }

            foreach ($likes as $item) {
                $like_cid = $item['cid'];

                // 初回ループで最新のCIDを記録
                if ($newest_like_cid === null) {
                    $newest_like_cid = $like_cid;
                }

                // 既に同期済みのいいねに到達した場合、処理を打ち切る
                if ($last_synced_like_cid && $like_cid === $last_synced_like_cid) {
                    $this->info("いいね: 既に同期済みのCID {$like_cid} に到達しました。");
                    $cursor = null; // ループを終了させる
                    break;
                }

                $subject = $item['value']['subject'];
                $created_at = Carbon::parse($item['value']['createdAt'])->setTimezone(config('app.timezone'));

                preg_match('/^at:\/\/(did:plc:[a-zA-Z0-9]+)\//', $item['uri'], $matches);
                $creator_did = $matches[1] ?? null;

                Like::updateOrCreate(
                    ['cid' => $like_cid, 'did' => $user->did],
                    [
                        'uri'            => $item['uri'],
                        'created_by_did' => $creator_did,
                        'post_uri'       => $subject['uri'],
                        'created_at'     => $created_at,
                    ]
                );
                if (!isset($daily_data[$created_at->format('Y-m-d')])) {
                    $daily_data[$created_at->format('Y-m-d')] = 0;
                }
                $daily_data[$created_at->format('Y-m-d')] += 1;
                $new_count += 1;
            }

            $this->info(sprintf(
                '[likes] ページ取得完了 (新規 %s件) cursor=%s',
                number_format($new_count),
                $cursor
            ));
        } while ($cursor);

        // last_synced_like_cid の更新
        if ($newest_like_cid) {
            $user->last_synced_like_cid = $newest_like_cid;
            $user->save();
        }

        $total = collect($daily_data)->sum();
        $this->info(sprintf('[likes] 全体で %s件の新規いいねを登録しました。', number_format($total)));

        return $daily_data;
    }

    private function upsertDailyStats(User $user, array $post_daily_data, array $like_daily_data): void {
        $this->comment(sprintf('[stats] 日別統計のアップサート開始: %s', $user->handle));
        $dates = collect(array_keys($post_daily_data))->merge(array_keys($like_daily_data))->unique()->all();
        $total_post_count = 0;
        foreach ($dates as $date) {
            $daily_stat = DailyStat::firstOrNew(
                ['did' => $user->did, 'date' => $date]
            );

            $daily_stat->posts_count += ($post_daily_data[$date]['posts_count'] ?? 0);
            $daily_stat->likes_count += ($like_daily_data[$date] ?? 0);
            $daily_stat->replies_count += ($post_daily_data[$date]['replies_count'] ?? 0);
            $daily_stat->reposts_count += ($post_daily_data[$date]['reposts_count'] ?? 0);
            $daily_stat->save();
            $total_post_count += $daily_stat->posts_count;

            $this->info(
                sprintf('[stats] %s => posts:%d, likes:%d, replies:%d, reposts:%d',
                    $date,
                    $daily_stat->posts_count,
                    $daily_stat->likes_count,
                    $daily_stat->replies_count,
                    $daily_stat->reposts_count)
            );
        }
        $user->save();

        $this->info('[stats] 日別統計のアップサート完了');
    }

    private function storeMedia(Post $post, array $item): void {
        $record = $item['record'];
        $record_embed = $record['embed'];
        $embed = $item['embed'];
        $media_type = $record_embed['$type'];
        switch ($media_type) {
            case 'app.bsky.embed.images':
                foreach ($embed['images'] as $i => $image) {
                    $values = [
                        'post_cid'            => $post->cid,
                        'type'                => $media_type,
                        'alt_text'            => $image['alt'] ?? null,
                        'size'                => $record_embed['images'][$i]['image']['size'],
                        'mime'                => $record_embed['images'][$i]['image']['mimeType'],
                        'fullsize_url'        => $image['fullsize'],
                        'thumbnail_url'       => $image['thumb'],
                        'aspect_ratio_width'  => $image['aspectRatio']['width'] ?? null,
                        'aspect_ratio_height' => $image['aspectRatio']['height'] ?? null,
                    ];
                    $media = Media::query()
                        ->where('post_cid', '=', $post->cid)
                        ->where('fullsize_url', '=', $values['fullsize_url'])
                        ->first();
                    if (!($media instanceof Media)) {
                        $media = Media::make();
                    }
                    foreach ($values as $key => $value) {
                        $media->{$key} = $value;
                    }
                    $media->save();
                }
                break;
            case 'app.bsky.embed.video':
                $video = $embed;
                $values = [
                    'post_cid'            => $post->cid,
                    'type'                => $media_type,
                    'alt_text'            => $video['alt'] ?? null,
                    'size'                => $record_embed['video']['size'],
                    'mime'                => $record_embed['video']['mimeType'],
                    'fullsize_url'        => $video['playlist'],
                    'thumbnail_url'       => $video['thumbnail'],
                    'aspect_ratio_width'  => $video['aspectRatio']['width'] ?? null,
                    'aspect_ratio_height' => $video['aspectRatio']['height'] ?? null,
                ];
                $media = Media::query()
                    ->where('post_cid', '=', $post->cid)
                    ->where('fullsize_url', '=', $values['fullsize_url'])
                    ->first();
                if (!($media instanceof Media)) {
                    $media = Media::make();
                }
                foreach ($values as $key => $value) {
                    $media->{$key} = $value;
                }
                $media->save();
                break;
            default:
                break;
        }
    }
}
