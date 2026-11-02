<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\SearchReport;
use App\Services\OpenSeoMcp;
use App\Services\SearchConsoleCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SearchIntegrationController extends Controller
{
    public function import(Request $request, SearchConsoleCsv $parser)
    {
        $data = $request->validate([
            'report' => ['required', 'file', 'max:2048'],
            'property' => ['required', 'string', 'max:255', 'regex:~^(sc-domain:[a-zA-Z0-9.-]+|https://[a-zA-Z0-9.-]+(?::[0-9]+)?/[^\s]*)$~'],
            'period_start' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start', 'before_or_equal:today'],
        ]);
        $rows = $parser->parse($request->file('report'));
        $hash = hash('sha256', $data['property'].'|'.$data['period_start'].'|'.$data['period_end'].'|'.json_encode($rows));
        DB::transaction(function () use ($rows, $hash, $data, $request) {
            $report = SearchReport::firstOrCreate(['fingerprint' => $hash], [
                'property' => $data['property'], 'period_start' => $data['period_start'], 'period_end' => $data['period_end'],
                'rows' => $rows, 'clicks' => array_sum(array_column($rows, 'clicks')), 'impressions' => array_sum(array_column($rows, 'impressions')), 'uploaded_by' => $request->user()->id,
            ]);
            if ($report->wasRecentlyCreated) {
                AuditEvent::record($request->user(), 'search_report.imported', $report);
            }
        });

        return redirect()->route('staff.growth', 'analytics')->with('status', 'Search Console report imported. Duplicate uploads are ignored; no search-engine submission was made.');
    }

    public function check(Request $request, OpenSeoMcp $client)
    {
        try {
            $count = $client->check();
        } catch (RuntimeException $exception) {
            DB::table('integration_checks')->updateOrInsert(['provider' => 'openseo'], ['status' => 'failed', 'tool_count' => 0, 'checked_at' => now()]);

            return back()->withErrors(['openseo' => $exception->getMessage()]);
        }
        DB::table('integration_checks')->updateOrInsert(['provider' => 'openseo'], ['status' => 'verified', 'tool_count' => $count, 'checked_at' => now()]);
        AuditEvent::record($request->user(), 'openseo.verified', $request->user());

        return back()->with('status', 'OpenSEO MCP verified using free account and project reads. Research was not run.');
    }
}
