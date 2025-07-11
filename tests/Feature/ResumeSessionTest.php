<?php

namespace Tests\Feature;

use App\Http\Controllers\BlueskyController;
use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Mockery;
use Revolution\Bluesky\Facades\Bluesky;
use Tests\TestCase;

// セッション再開処理のトークン更新を検証するテスト

class ResumeSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--no-interaction' => true]);
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * セッションチェックが失敗した場合に更新が呼ばれるか
     */
    public function test_チェック失敗時にセッション更新される(): void
    {
        // モック用のセッション情報を投入
        Session::put('bluesky_session', [
            'accessJwt' => 'a',
            'refreshJwt' => 'b',
            'did' => 'did:test',
            'handle' => 'test',
        ]);

        // コントローラを生成
        $controller = new BlueskyController(new SettingService());

        // Blueskyライブラリの挙動をモック
        Bluesky::shouldReceive('withToken')->once()->andReturnSelf();
        Bluesky::shouldReceive('check')->once()->andReturnFalse();
        Bluesky::shouldReceive('refreshSession')->once();

        // メソッド実行
        $controller->resumeSession();

        $this->assertTrue(true);
    }

    /**
     * セッションチェックが成功した場合に更新が呼ばれないか
     */
    public function test_チェック成功時に更新されない(): void
    {
        Session::put('bluesky_session', [
            'accessJwt' => 'a',
            'refreshJwt' => 'b',
            'did' => 'did:test',
            'handle' => 'test',
        ]);

        $controller = new BlueskyController(new SettingService());

        // Blueskyライブラリの挙動をモック
        Bluesky::shouldReceive('withToken')->once()->andReturnSelf();
        Bluesky::shouldReceive('check')->once()->andReturnTrue();
        Bluesky::shouldReceive('refreshSession')->never();

        // メソッド実行
        $controller->resumeSession();

        $this->assertTrue(true);
    }
}
