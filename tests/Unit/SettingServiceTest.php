<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// SettingService の基本機能を確認するテスト

class SettingServiceTest extends TestCase
{
    private SettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--no-interaction' => true]);
        $this->service = new SettingService();
    }

    /**
     * ブール値の設定と取得ができるか確認
     */
    public function test_ブール値を保存し取得できる(): void
    {
        // true を保存
        $this->service->set('feature_enabled', true, 'boolean');
        // 保存した値が取得できるか
        $this->assertTrue(
            $this->service->get('feature_enabled')
        );
    }

    /**
     * 設定が存在しない場合のデフォルト値返却を確認
     */
    public function test_存在しないキーはデフォルトを返す(): void
    {
        // 未設定キーを取得するとデフォルトが返る
        $result = $this->service->get('missing_key', 'default');
        $this->assertSame('default', $result);
    }

    /**
     * 更新時にキャッシュが無効化されるか確認
     */
    public function test_設定更新でキャッシュがクリアされる(): void
    {
        // 初回保存
        $this->service->set('cache_test', 'first', 'string');
        $this->assertSame('first', $this->service->get('cache_test'));

        // データベースだけ変更してもキャッシュが残る
        DB::table('settings')->where('key', 'cache_test')->update(['value' => 'second']);
        $this->assertSame('first', $this->service->get('cache_test'));

        // 再保存するとキャッシュがクリアされる
        $this->service->set('cache_test', 'third', 'string');
        $this->assertSame('third', $this->service->get('cache_test'));
    }
}
