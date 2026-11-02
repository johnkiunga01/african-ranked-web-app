<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

class LaunchReadiness
{
    public function acceptedArticles(): int
    {
        return Article::whereNull('trashed_at')->whereNotNull('published_revision_id')->with(['publishedRevision', 'author'])->get()
            ->filter(function ($article) {
                $revision = $article->publishedRevision;

                return $revision && $revision->status->value === 'published' && $revision->reviewed_by && $revision->reviewed_at
                    && $article->author?->is_active && $article->author?->hasVerifiedEmail()
                    && $revision->editorial_evidence && ! app(PublicationQuality::class)->inspect($revision)['failures'];
            })->count();
    }

    public function report(): array
    {
        $checks = [];
        $add = function ($name, $passed, $detail) use (&$checks) {
            $checks[] = ['name' => $name, 'passed' => (bool) $passed, 'detail' => $detail];
        };
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: '';
        $key = config('app.key');
        $decodedKey = str_starts_with($key ?? '', 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $add('Production environment', app()->environment('production'), 'Run against the intended production configuration, not the isolated review instance.');
        $add('Required runtime extensions', extension_loaded('xmlwriter') && extension_loaded('pdo') && extension_loaded('mbstring'), 'QR generation, database access and Unicode editorial processing require XMLWriter, PDO and mbstring.');
        $add('Debug output disabled', ! config('app.debug'), 'Production must not display stack traces or configuration values.');
        $add('Application encryption key', is_string($decodedKey) && strlen($decodedKey) === 32, 'A stable 256-bit application key is required; its value is never displayed.');
        $add('Permanent HTTPS origin', str_starts_with(config('app.url'), 'https://') && filter_var('https://'.$host, FILTER_VALIDATE_URL) && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true) && ! preg_match('/(?:tunnelmole\.net|trycloudflare\.com|\.test)$/i', $host), 'Use the owned production domain, not a temporary tunnel or test host.');
        $add('Preview isolation disabled for production', ! config('review.enabled') && ! config('seo.preview'), 'Do not change the active review instance; production must intentionally use its own configuration.');
        $add('Secure session cookie', config('session.secure') && config('session.http_only') && config('session.same_site') === 'lax', 'Require HTTPS-only, HttpOnly cookies with SameSite=Lax.');
        $add('Session payload encryption', config('session.encrypt'), 'Encrypt persisted session payloads as well as cookies.');
        $add('Admin MFA enforcement', config('security.require_admin_two_factor'), 'Require super-admin enrollment before protected workspace access.');
        try {
            DB::connection()->getPdo();
            $add('Database connection', true, 'Connection established without displaying host, username or password.');
            $admins = User::where('role', 'super_admin')->where('is_active', true)->whereNotNull('email_verified_at');
            $count = (clone $admins)->count();
            $unprotected = (clone $admins)->whereNull('two_factor_confirmed_at')->count();
            $add('Active administrators enrolled in MFA', $count > 0 && $unprotected === 0, $count.' active verified administrators; '.$unprotected.' awaiting enrollment.');
            $accepted = $this->acceptedArticles();
            $target = config('growth.article_target');
            $add('Accepted published content target', $accepted >= $target, $accepted.' / '.$target.' unique published articles pass the master evidence checks and have a recorded reviewer. The phased plan contains '.config('growth.planned_articles').' briefs.');
        } catch (Throwable) {
            $add('Database and editorial evidence', false, 'The database or acceptance register could not be checked. No credential/error details are exposed.');
        }
        $add('Persistent session store', ! in_array(config('session.driver'), ['array', 'cookie', null], true), 'Use a persistent server-side session store appropriate for the production topology.');
        $add('Persistent cache and locks', ! in_array(config('cache.default'), ['array', 'null', null], true), 'Scheduler locks and request limits must persist across HTTP requests.');
        $add('Queued work configured', ! in_array(config('queue.default'), ['sync', 'null', null], true), 'A durable queue connection is required; worker supervision still needs operational proof.');
        $mailer = config('mail.default');
        $add('Non-test mail transport', ! in_array($mailer, ['log', 'array', null], true), 'This checks configuration only; prove inbox delivery and recovery separately.');
        $add('Image editor processing and persistent media', extension_loaded('gd') && function_exists('imagewebp') && is_writable(storage_path('app/public')), 'Author images require GD/WebP and write access to the mounted editorial media volume.');
        $add('Responsive content navigation endpoints', Route::has('rankings') && Route::has('regional.rankings') && Route::has('staff.articles.media.upload') && Route::has('editorial-art.show'), 'Rankings, regional views, image uploads and original editorial illustrations must have registered routes.');
        $add('Newsletter not advertised before delivery works', ! config('editorial.newsletter_enabled'), 'The signup UI stays hidden until a mail provider, double opt-in and unsubscribe flow have been verified.');
        $add('Built assets without dev server', is_file(public_path('build/manifest.json')) && ! is_file(public_path('hot')), 'Deploy versioned assets without a Vite hot-server pointer.');

        return ['checks' => $checks, 'passed' => count(array_filter($checks, fn ($c) => $c['passed'])), 'total' => count($checks),
            'manual_gates' => ['Production database concurrency and performance tests', 'Database/media backup restore and rollback rehearsal', 'Durable scheduler, queue workers and monitoring', 'Email delivery, newsletter confirmation and unsubscribe', 'Legal identity, privacy, consent and commercial disclosures', 'Editorial fact/source/media-rights acceptance and full crawl', 'Reader/admin mobile, tablet, desktop accessibility and keyboard signoff', 'Core Web Vitals / production performance signoff', 'Founder approval of the concrete release candidate']];
    }
}
