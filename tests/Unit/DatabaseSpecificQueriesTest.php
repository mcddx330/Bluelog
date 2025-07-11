<?php

namespace Tests\Unit;

use App\Models\Traits\HasDatabaseSpecificQueries;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DatabaseSpecificQueriesTest extends TestCase
{
    use HasDatabaseSpecificQueries {
        getDayOfWeekSql as public traitGetDayOfWeekSql;
        getHourSql as public traitGetHourSql;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * SQLite環境で正しいSQLが生成されるか確認
     */
    public function test_SQLite用SQLを返す(): void
    {
        $mockConn = Mockery::mock();
        $mockConn->shouldReceive('getDriverName')->once()->andReturn('sqlite');
        DB::shouldReceive('connection')->once()->andReturn($mockConn);

        $this->assertSame(
            "CAST(strftime('%w', posted_at) AS INTEGER)",
            $this->traitGetDayOfWeekSql()
        );

        $mockConn = Mockery::mock();
        $mockConn->shouldReceive('getDriverName')->once()->andReturn('sqlite');
        DB::shouldReceive('connection')->once()->andReturn($mockConn);

        $this->assertSame(
            "CAST(strftime('%H', posted_at) AS INTEGER)",
            $this->traitGetHourSql()
        );
    }

    /**
     * MySQL環境で正しいSQLが生成されるか確認
     */
    public function test_MySQL用SQLを返す(): void
    {
        $mockConn = Mockery::mock();
        $mockConn->shouldReceive('getDriverName')->once()->andReturn('mysql');
        DB::shouldReceive('connection')->once()->andReturn($mockConn);

        $this->assertSame(
            '(DAYOFWEEK(posted_at) - 1)',
            $this->traitGetDayOfWeekSql()
        );

        $mockConn = Mockery::mock();
        $mockConn->shouldReceive('getDriverName')->once()->andReturn('mysql');
        DB::shouldReceive('connection')->once()->andReturn($mockConn);

        $this->assertSame(
            'HOUR(posted_at)',
            $this->traitGetHourSql()
        );
    }
}
