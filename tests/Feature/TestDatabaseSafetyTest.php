<?php

namespace Tests\Feature;

use RuntimeException;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class TestDatabaseSafetyTest extends TestCase
{
    public function test_test_guard_refuses_file_database_and_dsn_without_revealing_credentials(): void
    {
        $original = config('database.connections.sqlite');
        $originalDriver = config('database.default');
        config(['database.default' => 'sqlite']);
        try {
            config(['database.connections.sqlite.database' => base_path('database/database.sqlite')]);
            try {
                TestDatabaseGuard::assertSafe($this->app);
                $this->fail('The development file database must be refused.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('refuse this target', $exception->getMessage());
            }
            config(['database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => 'mysql://fixture-private-dsn']);
            try {
                TestDatabaseGuard::assertSafe($this->app);
                $this->fail('An inherited DSN must be refused.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('refuse DB_URL', $exception->getMessage());
                $this->assertStringNotContainsString('fixture-private-dsn', $exception->getMessage());
            }
        } finally {
            config(['database.connections.sqlite' => $original, 'database.default' => $originalDriver]);
        }
    }
}
