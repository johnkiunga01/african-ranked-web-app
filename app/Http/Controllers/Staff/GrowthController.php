<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\GrowthPlan;
use App\Services\GoogleReports;
use App\Services\GrowthOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowthController extends Controller
{
    public function index(string $section, GrowthOverview $overview, GoogleReports $google)
    {
        abort_unless(in_array($section, ['seo', 'analytics', 'revenue', 'integrations'], true), 404);
        $reports = [
            'ga4' => $section === 'analytics' ? $google->analytics() : null,
            'gsc' => $section === 'seo' ? $google->searchConsole() : null,
            'googleEmail' => $google->serviceAccountEmail(),
        ];

        return view('staff.growth', ['section' => $section] + $reports + $overview->data());
    }

    public function refreshGoogle(Request $request, GoogleReports $google)
    {
        $section = $request->validate(['section' => ['required', Rule::in(['seo', 'analytics'])]])['section'];
        $google->forget();
        AuditEvent::record($request->user(), 'growth.google_reports_refreshed', $request->user(), ['section' => $section]);

        return redirect()->route('staff.growth', $section)->with('status', 'Reports refreshed from Google.');
    }

    public function update(Request $request, string $provider)
    {
        abort_unless(array_key_exists($provider, config('growth.providers')), 404);
        $data = $request->validate([
            'stage' => ['required', Rule::in(['planned', 'evaluating', 'implementation_requested', 'paused'])],
            'reference' => ['nullable', 'string', 'max:120', 'regex:/\A[\pL\pN ._:\/\-]+\z/u'],
            'version' => ['required', 'integer', 'min:0'],
        ]);
        DB::transaction(function () use ($data, $provider, $request) {
            // Serialize creation as well as subsequent updates of each allowlisted provider.
            GrowthPlan::firstOrCreate(['provider' => $provider]);
            $plan = GrowthPlan::where('provider', $provider)->lockForUpdate()->firstOrFail();
            $expected = $plan->updated_by ? $plan->version : 0;
            if ((int) $data['version'] !== (int) $expected) {
                throw ValidationException::withMessages(['version' => 'This plan changed. Reload the page before saving.']);
            }
            $plan->stage = $data['stage'];
            $plan->reference = $data['reference'] ?? null;
            $plan->updated_by = $request->user()->id;
            $plan->version++;
            $plan->save();
            AuditEvent::record($request->user(), 'growth.plan_updated', $plan, ['provider' => $provider, 'stage' => $plan->stage]);
        });

        return redirect()->route('staff.growth', ['section' => 'integrations'])->with('status', 'Plan saved. No external service was connected or activated.');
    }
}
