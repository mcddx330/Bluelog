<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

// 環境が正しく構築されているかを確認するテスト
class EnvironmentTest extends TestCase
{
    /**
     * テスト実行前にデータベースを初期化
     */
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--no-interaction' => true]);
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // 例示的な簡易テスト
    public function test_基本機能が動作する(): void
    {
        $this->assertTrue(true);
    }
}
