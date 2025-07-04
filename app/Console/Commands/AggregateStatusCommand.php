<?php

namespace App\Console\Commands;

use App\Models\DailyStat;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Revolution\Bluesky\BlueskyManager;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\LegacySession;
use App\Models\Media;

class AggregateStatusCommand extends Command {
    protected $signature   = 'status:aggregate';
    protected $description = 'Blueskyユーザーのアクティビティを取得し、集計します。';

    public function handle(): void {
        $this->output->writeln('<info>Blueskyステータス集計を開始します...</info>');

        // is_fetching が false のユーザーのみ取得
        $users = User::query()
            ->where('is_fetching', false)
            ->orWhereNull('is_fetching')
            ->get();

        if ($users->isEmpty()) {
            $this->output->writeln('<comment>処理対象のユーザーがありません。</comment>');

            return;
        }

        $this->output->writeln(sprintf('<info>%d 人のユーザーを処理します。</info>', $users->count()));

        foreach ($users as $user) {
            $this->output->writeln(sprintf('<comment>==== ユーザー処理開始: %s ====</comment>', $user->handle));

            try {
                $bluesky = $this->prepareBlueskyClient($user);
                $this->output->writeln(sprintf('<info>[Client] 準備完了: %s</info>', $user->handle));

                DB::transaction(function () use ($user) {
                    $user->markFetching();
                });

                DB::transaction(function () use ($bluesky, $user) {
                    // プロフィール更新
                    $this->updateUserProfile($bluesky, $user);

                    // 投稿といいねの取得・保存
                    $post_daily_data = $this->fetchAndStorePosts($bluesky, $user);
                    $like_daily_data = $this->fetchAndStoreLikes($bluesky, $user);

                    // 日別統計を統合して保存
                    $this->upsertDailyStats($user, $post_daily_data, $like_daily_data);

                });

                DB::transaction(function () use ($user) {
                    $user->unmarkFetching();
                });

                $this->output->writeln(sprintf('<comment>==== ユーザー処理終了: %s ====</comment>', $user->handle));
            } catch (\Throwable $e) {
                Log::error(sprintf(
                    'ユーザー %s の集計中にエラー: %s %d . %s',
                    $user->handle,
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                ));
                $this->output->writeln(sprintf('<error>エラー: %s（詳細はログ）</error>', $e->getMessage()));
            } finally {
                // フラグ解除と最終取得時刻更新を保証
                $user->is_fetching = false;
                $user->last_fetched_at = now();
                $user->save();
                $this->output->writeln(sprintf('<comment>最終取得時刻を更新: %s</comment>', $user->last_fetched_at));
            }
        }

        $this->output->writeln('<info>全ユーザーの集計処理が完了しました。</info>');
    }

    private function prepareBlueskyClient(User $user): BlueskyManager {
        $this->output->writeln(sprintf('<comment>[prepare] セッションデータを作成: %s</comment>', $user->handle));
        $session_data = [
            'accessJwt'  => $user->access_jwt,
            'refreshJwt' => $user->refresh_jwt,
            'did'        => $user->did,
            'handle'     => $user->handle,
        ];
        $bluesky = Bluesky::withToken(LegacySession::create($session_data));

        try {
            $this->output->writeln('<comment>[prepare] トークンチェック実施</comment>');
            $bluesky->check();
        } catch (\Throwable) {
            $this->output->writeln(sprintf('<comment>アクセストークン期限切れ: %s → リフレッシュ実施</comment>', $user->handle));
            $response = $bluesky->refreshSession();
            $this->output->writeln('<info>[prepare] トークンリフレッシュ完了</info>');

            $user->access_jwt = $response->access_jwt;
            $user->refresh_jwt = $response->refresh_jwt;
            $user->save();
            $this->output->writeln('<comment>[prepare] 新しいトークンをユーザーに保存</comment>');

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
        $this->output->writeln(sprintf('<comment>[profile] %s のプロフィール取得中...</comment>', $user->handle));
        $profile_response = $bluesky->getProfile($user->handle);
        $profile_data = json_decode($profile_response->getBody(), true);

        $user->display_name = $profile_data['displayName'] ?? $user->display_name;
        $user->description = $profile_data['description'] ?? $user->description;
        $user->avatar_url = $profile_data['avatar'] ?? $user->avatar_url;
        $user->banner_url = $profile_data['banner'] ?? $user->banner_url;
        $user->save();
        $this->output->writeln(sprintf('<info>[profile] %s のプロフィールを更新しました。</info>', $user->handle));
    }

    private function fetchAndStorePosts(BlueskyManager $bluesky, User $user): array {
        $this->output->writeln(sprintf('<comment>[posts] %s の投稿取得開始...</comment>', $user->handle));
        $daily_data = [];
        $cursor = null;

        do {
            $response = $bluesky->getAuthorFeed(actor: $user->handle, limit: 100, cursor: $cursor);
            $body = json_decode($response->getBody(), true);
            $cursor = $body['cursor'] ?? null;
            $feeds = $body['feed'] ?? [];

            $cids = collect($body['feed'] ?? [])->pluck('post.cid')->all();
            $existing_posts = Post::query()
                ->whereIn('cid', $cids)
                ->where('did', $user->did)
                ->get();
            $new_feeds = [];
            foreach ($feeds as $feed) {
                if (!$existing_posts->contains('cid', $feed['post']['cid'])) {
                    $new_feeds[] = $feed;
                }
            }

            $new_feeds = $feeds;
            $new_count = 0;
            foreach ($new_feeds ?? [] as $item) {
                $post_uri = $item['post']['uri'];
                if (Post::where('uri', $post_uri)->exists()) {
                    continue;
                }
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

                $post = Post::create([
                    'uri'             => $post_uri,
                    'cid'             => $item['post']['cid'],
                    'did'             => $user->did,
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
                ]);

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
                                    \App\Models\Hashtag::create([
                                        'post_id' => $post->id,
                                        'tag'     => ltrim($tag, '#'),
                                    ]);
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

            $this->output->writeln(sprintf('<info>[posts] ページ取得完了 (新規%d件) cursor=%s</info>', $new_count, $cursor));
        } while ($cursor);

        $total_posts = collect($daily_data)->sum('posts_count');
        $total_replies = collect($daily_data)->sum('replies_count');
        $total_reposts = collect($daily_data)->sum('reposts_count');

        $this->output->writeln(sprintf('<info>[posts] 全体で新規投稿 %d件, リプライ %d件, リポスト %d件 を登録しました。</info>', $total_posts, $total_replies, $total_reposts));

        return $daily_data;
    }

    private function fetchAndStoreLikes(BlueskyManager $bluesky, User $user): array {
        $this->output->writeln(sprintf('<comment>[likes] %s のいいね取得開始...</comment>', $user->handle));
        $daily_data = [];
        $cursor = null;

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
            $existing_likes = Like::query()
                ->whereIn('cid', collect($likes)->pluck('cid'))
                ->where('did', $user->did)
                ->get();

            $existing_cids = $existing_likes->pluck('cid')->all();
            $new_likes = array_filter($likes, function ($item) use ($existing_cids) {
                return !in_array($item['cid'], $existing_cids, true);
            });

            $new_count = 0;
            foreach ($new_likes as $item) {
                $subject = $item['value']['subject'];
                $created_at = Carbon::parse($item['value']['createdAt'])->setTimezone(config('app.timezone'));

                preg_match('/^at:\/\/(did:plc:[a-zA-Z0-9]+)\//', $item['uri'], $matches);
                $creator_did = $matches[1] ?? null;

                Like::create([
                    'cid'            => $item['cid'],
                    'did'            => $user->did,
                    'created_by_did' => $creator_did,
                    'post_uri'       => $subject['uri'],
                    'created_at'     => $created_at,
                ]);
                if (!isset($daily_data[$created_at->format('Y-m-d')])) {
                    $daily_data[$created_at->format('Y-m-d')] = 0;
                }
                $daily_data[$created_at->format('Y-m-d')] += 1;
                $new_count += 1;
            }

            $this->output->writeln(sprintf(
                '<info>[likes] ページ取得完了 (新規 %s件) cursor=%s</info>',
                number_format($new_count),
                $cursor
            ));
        } while ($cursor);

        $total = collect($daily_data)->sum();
        $this->output->writeln(sprintf('<info>[likes] 全体で %s件の新規いいねを登録しました。</info>', number_format($total)));

        return $daily_data;
    }

    private function upsertDailyStats(User $user, array $post_daily_data, array $like_daily_data): void {
        $this->output->writeln(sprintf('<comment>[stats] 日別統計のアップサート開始: %s</comment>', $user->handle));
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

            $this->output->writeln(
                sprintf('<info>[stats] %s => posts:%d, likes:%d, replies:%d, reposts:%d</info>',
                    $date,
                    $daily_stat->posts_count,
                    $daily_stat->likes_count,
                    $daily_stat->replies_count,
                    $daily_stat->reposts_count)
            );
        }
        $user->posts_count = $total_post_count;
        $user->save();

        $this->output->writeln('<info>[stats] 日別統計のアップサート完了</info>');
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
                        ->where('post_did', '=', $post->did)
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
                    ->where('post_did', '=', $post->did)
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
