<?php

use App\Models\ArticleRecommendation;
use App\Models\User;
use App\Services\EditorialWorkflow;
use App\Services\RecommendationGuidelines;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Contracts\Console\Kernel;

// Browser-test fixtures only; never invoked by normal seed or review startup.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (config('database.default') !== 'sqlite' || str_starts_with($database, '/tmp/african-ranked-browser-') === false || $app->environment('production')) {
    fwrite(STDERR, "Browser fixtures require the disposable browser-test SQLite database.\n");
    exit(1);
}
foreach ([['Browser Admin', 'admin@browser.example.test', 'super_admin'], ['Bulk Admin', 'bulk@browser.example.test', 'super_admin'], ['Credential Admin', 'credentials@browser.example.test', 'super_admin'], ['Editor QA Admin', 'editor-qa@browser.example.test', 'super_admin'], ['Browser Author', 'author@browser.example.test', 'author'], ['Other Author', 'other@browser.example.test', 'author']] as [$name, $email, $role]) {
    User::forceCreate(['name' => $name, 'email' => $email, 'password' => 'BrowserOnlyPassword123!', 'role' => $role, 'email_verified_at' => now(), 'is_active' => true]);
}
$admin = User::where('email', 'admin@browser.example.test')->firstOrFail();
for ($i = 1; $i <= 13; $i++) {
    $seed = app(Catalog::class)->data('articles')[0];
    $seed['slug'] = 'browser-inventory-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    $seed['title'] = 'Inventory fixture '.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    app(SeedArticleImporter::class)->import($seed, $admin);
}

// A completed proposal fixture exercises author acceptance without any live provider calls.
$author = User::where('email', 'author@browser.example.test')->firstOrFail();
$sources = [];
$facts = [];
for ($i = 1; $i <= 5; $i++) {
    $sources[] = ['title' => 'Synthetic source '.$i, 'publisher' => 'Fixture publisher', 'url' => 'https://source.example.test/'.$i, 'accessed_at' => now()->toDateString()];
    if ($i < 5) {
        $facts[] = ['fact' => 'Synthetic fact '.$i, 'source_url' => $sources[$i - 1]['url'], 'verified_at' => now()->toDateString()];
    }
}
$body = "## Reporting\n\n".str_repeat('Synthetic reporting validates the isolated recommendation workflow without making factual claims. ', 150);
foreach ($sources as $source) {
    $body .= "\n\n[{$source['title']}]({$source['url']})";
}
$faqs = "Synthetic question one?\nSynthetic answer.\n\nSynthetic question two?\nSynthetic answer.\n\nSynthetic question three?\nSynthetic answer.";
$article = app(EditorialWorkflow::class)->save($author, ['slug' => 'recommendation-browser-demo', 'title' => 'Current workflow demonstration', 'excerpt' => 'Disposable test content, not editorial reporting.',
    'category_slug' => 'travel', 'body_markdown' => $body, 'image' => 'https://image.example.test/recommendation.jpg', 'source_notes' => 'Synthetic isolated notes.', 'faqs_text' => $faqs,
    'editorial_evidence' => ['content_type' => 'guide', 'answer' => str_repeat('Synthetic answer ', 25), 'sources' => $sources, 'facts' => $facts, 'last_verified_at' => now()->toDateString(), 'image_rights' => 'Synthetic test record, not an actual licence.']]);
$guidelines = app(RecommendationGuidelines::class);
ArticleRecommendation::create(['article_id' => $article->id, 'source_revision_id' => $article->current_revision_id, 'user_id' => $author->id, 'purpose' => 'editorial_recommendation', 'provider' => 'gemini', 'status' => 'ready',
    'payload' => ['session_version' => $author->session_version, 'owner_id' => $author->id, 'model' => 'mocked-browser-provider',
        'guidelines' => $guidelines->snapshot($article), 'guidelines_fingerprint' => $guidelines->fingerprint($article),
        'research_date' => now()->toIso8601String(), 'changes' => ['Opening summary', 'Article structure and wording', 'Search description'],
        'draft' => ['title' => 'Suggested workflow demonstration', 'excerpt' => 'A clearer disposable introduction.', 'body_markdown' => $body, 'seo_title' => 'A clear test search title', 'meta_description' => 'A clear test search description.', 'faqs_text' => $faqs]]]);
