<?php

namespace Tests\Feature;

use App\Models\InvitationCode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Session\LegacySession;
use Mockery;
use Tests\TestCase;

// 登録モードごとの挙動を検証するテスト
class RegistrationModeTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // データベースを毎回初期化しクリーンな状態を保つ
        Artisan::call('migrate:fresh', ['--no-interaction' => true]);

        // アプリケーションキーを生成
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
        // キューと外部HTTPリクエストをモック
        Queue::fake();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        // モックを解放して後処理
        Mockery::close();

        parent::tearDown();
    }

    // シングルユーザーモードで他ユーザーのログインを拒否するか確認
    public function test_シングルユーザーモードで別ユーザーが拒否される(): void
    {
        // 利用を許可するユーザーを生成
        $allowed_user = User::factory()->create([
            'did' => 'did:allowed',
            'handle' => 'allowed.bsky.social',
            'is_admin' => true,
        ]);

        // シングルユーザーモード用の設定値
        Setting::create([
            'key' => 'registration_mode',
            'value' => 'single_user_only',
        ]);
        Setting::create([
            'key' => 'allowed_single_user_did',
            'value' => 'did:allowed',
        ]);

        // Blueskyセッションをモックし別ユーザーとしてログインを試みる
        $session = LegacySession::create([
            'accessJwt' => 'access',
            'refreshJwt' => 'refresh',
            'did' => 'did:other',
            'handle' => 'other.bsky.social',
        ]);
        $agent = Mockery::mock(Agent::class);
        $agent->shouldReceive('session')->andReturn($session);

        // ログイン処理だけをモックしプロフィール取得は呼ばれない想定
        Bluesky::shouldReceive('login->agent')->once()->andReturn($agent);
        Bluesky::shouldReceive('getProfile')->never();

        // 実際のログインリクエスト
        $response = $this->post('/login', [
            'identifier' => 'other',
            'password' => 'password',
        ]);

        // シングルユーザーモードのためログイン画面へリダイレクトされる
        $response->assertRedirect('/login');
        $response->assertSessionHas('error_message', 'このBluelogインスタンスは特定のアカウントのみに制限されています。');
        $this->assertDatabaseCount('users', 1);
    }

    // 招待コード必須モードで有効なコードがあればログインできるか確認
    public function test_招待コード必須モードで有効なコードならログインできる(): void
    {
        // 招待コード発行者となる管理ユーザー
        $issuer = User::factory()->create([
            'did' => 'did:issuer',
            'handle' => 'issuer.bsky.social',
            'is_admin' => true,
        ]);

        // 招待コード必須モードに設定
        Setting::create([
            'key' => 'registration_mode',
            'value' => 'invitation_required',
        ]);

        // 使用可能な招待コードを1つ発行
        $invitation_code = InvitationCode::create([
            'code' => 'ABCDEFGH12345678',
            'issued_by_user_did' => $issuer->did,
            'usage_limit' => null,
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        // 招待コード利用者としてのセッションを用意
        $session = LegacySession::create([
            'accessJwt' => 'access',
            'refreshJwt' => 'refresh',
            'did' => 'did:new',
            'handle' => 'new.bsky.social',
        ]);
        $agent = Mockery::mock(Agent::class);
        $agent->shouldReceive('session')->andReturn($session);

        // ログイン後にユーザープロフィール取得をモック
        Bluesky::shouldReceive('login->agent')->once()->andReturn($agent);
        Bluesky::shouldReceive('getProfile')
            ->once()
            ->andReturn(new Response(Http::response([
                'displayName' => 'New User',
                'followersCount' => 0,
                'followsCount' => 0,
                'postsCount' => 0,
                'createdAt' => '2024-01-01T00:00:00Z',
            ])->wait()));

        // 招待コードを添えてログインを実行
        $response = $this->post('/login', [
            'identifier' => 'new',
            'password' => 'password',
            'invitation_code' => $invitation_code->code,
        ]);

        // 新規ユーザーのプロフィールページへリダイレクト
        $response->assertRedirect('/new.bsky.social');

        // ユーザーと招待コードの使用履歴が保存されているか確認
        $this->assertDatabaseHas('users', [
            'did' => 'did:new',
            'is_early_adopter' => true,
        ]);
        $this->assertDatabaseHas('invitation_code_usages', [
            'invitation_code_id' => $invitation_code->id,
            'used_by_user_did' => 'did:new',
        ]);
        $this->assertDatabaseHas('invitation_codes', [
            'id' => $invitation_code->id,
            'current_usage_count' => 1,
        ]);
    }
}
