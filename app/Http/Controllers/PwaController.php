<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PwaController extends Controller
{
    /**
     * Anonymous, aggregate-only product counters. No IP, cookie, user agent,
     * account ID, or persistent visitor identifier is retained in the table.
     */
    public function track(Request $request)
    {
        $values = $request->validate([
            'event' => ['required', Rule::in(['help_opened', 'prompt_accepted', 'installed', 'standalone_first_open'])],
            'platform' => ['required', Rule::in(['android', 'ios', 'desktop', 'other'])],
        ]);

        // Do not allow an iOS self-report to masquerade as Chromium's appinstalled event.
        if ($values['event'] === 'installed' && $values['platform'] === 'ios') {
            return response()->json(['message' => 'Unsupported installation event'], 422);
        }

        if (! Schema::hasTable('pwa_install_daily_stats')) {
            return response()->json(['available' => false], 503)->header('Cache-Control', 'no-store');
        }

        $now = now();
        // Atomic upsert compatible with both production Postgres and the SQLite test suite.
        DB::statement(
            'INSERT INTO pwa_install_daily_stats (tracked_on, platform, event_type, total, created_at, updated_at)
             VALUES (?, ?, ?, 1, ?, ?)
             ON CONFLICT (tracked_on, platform, event_type)
             DO UPDATE SET total = pwa_install_daily_stats.total + 1, updated_at = excluded.updated_at',
            [$now->toDateString(), $values['platform'], $values['event'], $now, $now]
        );

        return response()->json(['recorded' => true], 202)->header('Cache-Control', 'no-store');
    }

    /** Generate correctly-sized, branded PNGs from the site's approved Apple icon. */
    public function icon(string $size): Response
    {
        abort_unless(in_array($size, ['192', '512', '192-maskable', '512-maskable'], true), 404);
        $source = public_path('apple-touch-icon.png');
        abort_unless(is_file($source) && extension_loaded('gd'), 503);

        $original = @imagecreatefrompng($source);
        abort_unless($original !== false, 503);
        $pixels = str_starts_with($size, '192') ? 192 : 512;
        $maskable = str_contains($size, '-maskable');
        $canvas = imagecreatetruecolor($pixels, $pixels);
        $background = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $background);
        $inset = $maskable ? (int) round($pixels * 0.12) : 0;
        $targetSize = $pixels - 2 * $inset;
        $ok = imagecopyresampled(
            $canvas, $original, $inset, $inset, 0, 0, $targetSize, $targetSize,
            imagesx($original), imagesy($original)
        );
        imagedestroy($original);
        if (! $ok) {
            imagedestroy($canvas);
            abort(503);
        }

        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();
        imagedestroy($canvas);
        abort_unless(is_string($png) && $png !== '', 503);

        return response($png)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
