<?php

namespace App\Http\Controllers;

use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function __invoke(Request $request, Catalog $catalog): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'region' => ['required', Rule::in(array_keys($catalog->regions()))],
            'website' => ['nullable', 'string', 'max:0'],
            'consent' => ['accepted'],
        ]);
        $now = now();
        // A unique index and insertOrIgnore prevent duplicate rows without disclosing membership.
        DB::table('newsletter_subscribers')->insertOrIgnore([
            'email' => mb_strtolower(trim($data['email'])), 'region' => $data['region'],
            'consented_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return back()->with('newsletter_status', 'Thank you. Your subscription request has been saved.');
    }
}
