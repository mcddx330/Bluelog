<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--no-interaction' => true]);
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    public function test_設定なしでは拒否しない(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        $response->assertSee('Disallow:');
    }

    public function test_全て拒否設定で拒否される(): void
    {
        Setting::create(['key' => 'deny_all_crawlers', 'value' => '1']);
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        $response->assertSee('Disallow: /');
    }
}
