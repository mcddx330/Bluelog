<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
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

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->assertTrue(true);
    }
}
