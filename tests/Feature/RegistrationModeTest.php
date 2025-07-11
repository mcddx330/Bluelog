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

        Artisan::call('migrate:fresh', ['--no-interaction' => true]);

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        Queue::fake();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    // シングルユーザーモードで他ユーザーのログインを拒否するか確認
    public function test_シングルユーザーモードで別ユーザーが拒否される(): void
    {
        $allowed_user = User::factory()->create([
            'did' => 'did:allowed',
            'handle' => 'allowed.bsky.social',
            'is_admin' => true,
        ]);

        Setting::create([
            'key' => 'registration_mode',
            'value' => 'single_user_only',
        ]);
        Setting::create([
            'key' => 'allowed_single_user_did',
            'value' => 'did:allowed',
        ]);

        $session = LegacySession::create([
            'accessJwt' => 'access',
            'refreshJwt' => 'refresh',
            'did' => 'did:other',
            'handle' => 'other.bsky.social',
        ]);
        $agent = Mockery::mock(Agent::class);
        $agent->shouldReceive('session')->andReturn($session);

        Bluesky::shouldReceive('login->agent')->once()->andReturn($agent);
        Bluesky::shouldReceive('getProfile')->never();

        $response = $this->post('/login', [
            'identifier' => 'other',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error_message', 'このBluelogインスタンスは特定のアカウントのみに制限されています。');
        $this->assertDatabaseCount('users', 1);
    }

    // 招待コード必須モードで有効なコードがあればログインできるか確認
    public function test_招待コード必須モードで有効なコードならログインできる(): void
    {
        $issuer = User::factory()->create([
            'did' => 'did:issuer',
            'handle' => 'issuer.bsky.social',
            'is_admin' => true,
        ]);

        Setting::create([
            'key' => 'registration_mode',
            'value' => 'invitation_required',
        ]);

        $invitation_code = InvitationCode::create([
            'code' => 'ABCDEFGH12345678',
            'issued_by_user_did' => $issuer->did,
            'usage_limit' => null,
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $session = LegacySession::create([
            'accessJwt' => 'access',
            'refreshJwt' => 'refresh',
            'did' => 'did:new',
            'handle' => 'new.bsky.social',
        ]);
        $agent = Mockery::mock(Agent::class);
        $agent->shouldReceive('session')->andReturn($session);

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

        $response = $this->post('/login', [
            'identifier' => 'new',
            'password' => 'password',
            'invitation_code' => $invitation_code->code,
        ]);

        $response->assertRedirect('/new.bsky.social');

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
