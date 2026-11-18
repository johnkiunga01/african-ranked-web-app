<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PwaInstallController extends Controller
{
    public function __invoke()
    {
        $since = now()->subDays(29)->toDateString();
        $records = Schema::hasTable('pwa_install_daily_stats')
            ? DB::table('pwa_install_daily_stats')
                ->where('tracked_on', '>=', $since)
                ->orderByDesc('tracked_on')
                ->get()
            : collect();

        $totals = [
            'android_confirmed' => $records->where('platform', 'android')->where('event_type', 'installed')->sum('total'),
            'ios_first_opens' => $records->where('platform', 'ios')->where('event_type', 'standalone_first_open')->sum('total'),
            'other_first_opens' => $records->whereNotIn('platform', ['ios'])->where('event_type', 'standalone_first_open')->sum('total'),
            'help_opened' => $records->where('event_type', 'help_opened')->sum('total'),
            'prompt_accepted' => $records->where('event_type', 'prompt_accepted')->sum('total'),
        ];

        $daily = $records->groupBy('tracked_on')->map(function ($rows, $day) {
            return [
                'date' => $day,
                'android_confirmed' => $rows->where('platform', 'android')->where('event_type', 'installed')->sum('total'),
                'ios_first_opens' => $rows->where('platform', 'ios')->where('event_type', 'standalone_first_open')->sum('total'),
                'other_first_opens' => $rows->whereNotIn('platform', ['ios'])->where('event_type', 'standalone_first_open')->sum('total'),
                'help_opened' => $rows->where('event_type', 'help_opened')->sum('total'),
            ];
        })->values();

        return view('staff.pwa-installs', compact('totals', 'daily'));
    }
}
