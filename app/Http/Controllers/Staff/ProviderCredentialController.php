<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\ProviderCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderCredentialController extends Controller
{
    public function update(Request $request, string $provider)
    {
        abort_unless(in_array($provider, ['openseo', 'openai', 'gemini'], true), 404);
        $data = $request->validate(['secret' => ['required', 'string', 'min:16', 'max:1024', 'regex:/\A[\x21-\x7e]+\z/D']]);
        DB::transaction(function () use ($request, $provider, $data) {
            $record = ProviderCredential::updateOrCreate(['provider' => $provider], ['secret' => $data['secret'], 'updated_by' => $request->user()->id]);
            if ($provider === 'openseo') {
                DB::table('integration_checks')->where('provider', $provider)->delete();
            }
            AuditEvent::record($request->user(), 'credential.updated', $record, ['provider' => $provider]);
        });

        return redirect()->route('staff.growth', 'integrations')->with('status', 'Credential encrypted and saved. Its value will not be displayed. Verify the provider before research or generation.');
    }

    public function destroy(string $provider)
    {
        abort_unless(in_array($provider, ['openseo', 'openai', 'gemini'], true), 404);
        DB::transaction(function () use ($provider) {
            $record = ProviderCredential::where('provider', $provider)->first();
            if ($record) {
                AuditEvent::record(auth()->user(), 'credential.removed', $record, ['provider' => $provider]);
                $record->delete();
            }
            DB::table('integration_checks')->where('provider', $provider)->delete();
        });

        return redirect()->route('staff.growth', 'integrations')->with('status', 'Stored credential removed. A server environment credential, if configured, still takes precedence.');
    }
}
