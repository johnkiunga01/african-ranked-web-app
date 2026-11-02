<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use RuntimeException;

class TestDatabaseGuard
{
    public static function assertSafe(Application $app): void
    {
        if (! $app->environment('testing')) {
            throw new RuntimeException('Database tests require APP_ENV=testing and an isolated disposable database.');
        }
        $driver = config('database.default');
        $connection = config('database.connections.'.$driver, []);
        if (filled($connection['url'] ?? null)) {
            throw new RuntimeException('Database tests refuse DB_URL. Configure an explicit disposable test connection.');
        }
        if ($driver === 'sqlite' && ($connection['database'] ?? '') === ':memory:') {
            return;
        }
        if (in_array($driver, ['mysql', 'pgsql'], true) && in_array($connection['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true) && preg_match('/^[a-zA-Z0-9_]+_(?:test|testing|ci)$/D', $connection['database'] ?? '')) {
            return;
        }
        throw new RuntimeException('Database tests refuse this target. Use SQLite :memory: or a localhost database ending _test, _testing or _ci. No test migrations were run.');
    }
}
