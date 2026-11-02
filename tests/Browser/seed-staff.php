<?php

use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Contracts\Console\Kernel;

// Browser-test fixtures only; never invoked by normal seed or review startup.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (config('database.default') !== 'sqlite' || ! str_starts_with($database, '/tmp/african-ranked-browser-') || $app->environment('production')) {
    fwrite(STDERR, "Browser fixtures require the disposable browser-test SQLite database.\n");
    exit(1);
}
foreach ([['Browser Admin', 'admin@browser.example.test', 'super_admin'], ['Bulk Admin', 'bulk@browser.example.test', 'super_admin'], ['Credential Admin', 'credentials@browser.example.test', 'super_admin'], ['Browser Author', 'author@browser.example.test', 'author'], ['Other Author', 'other@browser.example.test', 'author']] as [$name, $email, $role]) {
    User::forceCreate(['name' => $name, 'email' => $email, 'password' => 'BrowserOnlyPassword123!', 'role' => $role, 'email_verified_at' => now(), 'is_active' => true]);
}
$admin = User::where('email', 'admin@browser.example.test')->firstOrFail();
for ($i = 1; $i <= 13; $i++) {
    $seed = app(Catalog::class)->data('articles')[0];
    $seed['slug'] = 'browser-inventory-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    $seed['title'] = 'Inventory fixture '.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    app(SeedArticleImporter::class)->import($seed, $admin);
}
