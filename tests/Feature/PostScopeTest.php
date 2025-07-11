<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// Postモデルの集計スコープを検証するテスト

class PostScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--no-interaction' => true]);
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    /**
     * 曜日と時間別に投稿数を集計できるか確認
     */
    public function test_投稿数を曜日と時間で集計できる(): void
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create(['did' => 'did:test', 'handle' => 'test.bsky.social']);

        // 1件目の投稿を登録
        Post::create([
            'uri' => 'at://did:test/post1',
            'cid' => 'cid1',
            'did' => $user->did,
            'rkey' => 'r1',
            'text' => 'one',
            'posted_at' => Carbon::parse('2024-01-01 10:00:00'),
            'indexed_at' => Carbon::parse('2024-01-01 10:00:00'),
            'is_repost' => false,
            'likes_count' => 0,
            'replies_count' => 0,
            'reposts_count' => 0,
        ]);

        // 2件目の投稿を登録
        Post::create([
            'uri' => 'at://did:test/post2',
            'cid' => 'cid2',
            'did' => $user->did,
            'rkey' => 'r2',
            'text' => 'two',
            'posted_at' => Carbon::parse('2024-01-02 15:00:00'),
            'indexed_at' => Carbon::parse('2024-01-02 15:00:00'),
            'is_repost' => false,
            'likes_count' => 0,
            'replies_count' => 0,
            'reposts_count' => 0,
        ]);

        // 曜日ごとの投稿数を取得して検証
        $by_day = Post::postsCountByDayOfWeek($user->did)->pluck('count', 'day_of_week')->toArray();
        $this->assertSame(1, $by_day[1]); // Monday
        $this->assertSame(1, $by_day[2]); // Tuesday

        // 時間帯ごとの投稿数を取得して検証
        $by_hour = Post::postsCountByHour($user->did)->pluck('count', 'hour')->toArray();
        $this->assertSame(1, $by_hour[10]);
        $this->assertSame(1, $by_hour[15]);
    }
}
