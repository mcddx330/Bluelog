<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;

trait HasDatabaseSpecificQueries {
    protected function getDayOfWeekSql(): string {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => 'CAST(strftime(\'%w\', posted_at) AS INTEGER)',
            'mysql' => '(DAYOFWEEK(posted_at) - 1)', // DAYOFWEEK returns 1 for Sunday, 7 for Saturday
            default => 'CAST(strftime(\'%w\', posted_at) AS INTEGER)', // Fallback for other drivers
        };
    }

    protected function getHourSql(): string {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => 'CAST(strftime(\'%H\', posted_at) AS INTEGER)',
            'mysql' => 'HOUR(posted_at)',
            default => 'CAST(strftime(\'%H\', posted_at) AS INTEGER)', // Fallback for other drivers
        };
    }
}
