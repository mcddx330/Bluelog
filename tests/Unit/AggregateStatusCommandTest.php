<?php

namespace Tests\Unit;

use App\Console\Commands\AggregateStatusCommand;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Revolution\Bluesky\BlueskyManager;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\LegacySession;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Console\OutputStyle;
use Tests\TestCase;

class AggregateStatusCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * トークンチェック失敗時にリフレッシュが行われるか確認
     */
    public function test_トークン失効時にリフレッシュされる(): void
    {
        Artisan::call('migrate:fresh', ['--no-interaction' => true]);
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
        $user = User::factory()->create([
            'did' => 'did:test',
            'handle' => 'test.bsky.social',
            'access_jwt' => 'oldA',
            'refresh_jwt' => 'oldB',
        ]);

        $command = new AggregateStatusCommand();
        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $buffer = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $buffer));

        $blueskyMock = Mockery::mock(BlueskyManager::class);
        $blueskyMock->shouldReceive('check')->once()->andThrow(new \Exception());
        $blueskyMock->shouldReceive('refreshSession')->once()->andReturn($blueskyMock);
        $blueskyMock->access_jwt = 'newA';
        $blueskyMock->refresh_jwt = 'newB';

        Bluesky::shouldReceive('withToken')
            ->once()
            ->andReturn($blueskyMock);
        Bluesky::shouldReceive('withToken')
            ->once()
            ->andReturn($blueskyMock);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('prepareBlueskyClient');
        $method->setAccessible(true);
        $result = $method->invoke($command, $user);

        $this->assertSame('newA', $user->fresh()->access_jwt);
        $this->assertSame($blueskyMock, $result);
    }
}
