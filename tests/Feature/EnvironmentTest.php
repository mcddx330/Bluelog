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

        // 毎回マイグレーションを実行しテーブルをリセット
        Artisan::call('migrate:fresh', ['--no-interaction' => true]);
        // 動作に必要なアプリケーションキーを生成
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    protected function tearDown(): void
    {
        // 現状特に後処理は無いが親クラスの処理を呼び出す
        parent::tearDown();
    }

    // 例示的な簡易テスト
    public function test_基本機能が動作する(): void
    {
        // PHPUnitが正しく機能するかを確認
        $this->assertTrue(true);
    }
}
