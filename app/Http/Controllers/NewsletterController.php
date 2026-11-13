<?php

namespace App\Http\Controllers;

use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class NewsletterController extends Controller
{
    public function __invoke(Request $request, Catalog $catalog): RedirectResponse
    {
        abort_unless(config('editorial.newsletter_enabled'), 404);
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'region' => ['required', Rule::in(array_keys($catalog->regions()))],
            'website' => ['nullable', 'string', 'max:0'],
            'consent' => ['accepted'],
        ]);
        $now = now();
        $email = mb_strtolower(trim($data['email']));

        $pending = DB::transaction(function () use ($data, $email, $now) {
            DB::table('newsletter_subscribers')->insertOrIgnore([
                'email' => $email, 'region' => $data['region'],
                'consented_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $subscriber = DB::table('newsletter_subscribers')->where('email', $email)->lockForUpdate()->first();
            if ($subscriber->verified_at && ! $subscriber->unsubscribed_at) {
                return null;
            }
            if ($subscriber->verification_sent_at
                && Carbon::parse($subscriber->verification_sent_at)->greaterThan($now->copy()->subMinutes(15))) {
                return null;
            }
            $token = bin2hex(random_bytes(32));
            $digest = hash('sha256', $token);
            DB::table('newsletter_subscribers')->where('id', $subscriber->id)->update([
                'region' => $data['region'], 'consented_at' => $now,
                'verification_token_hash' => $digest,
                'verification_expires_at' => $now->copy()->addDay(),
                'verification_sent_at' => $now, 'updated_at' => $now,
            ]);

            return ['id' => $subscriber->id, 'email' => $email, 'token' => $token, 'digest' => $digest];
        });

        if ($pending) {
            $url = route('newsletter.confirm', ['subscriber' => $pending['id'], 'token' => $pending['token']]);
            try {
                Mail::raw(
                    "Confirm your African Ranked newsletter subscription by opening:\n".$url.
                    "\n\nThis link expires in 24 hours. If you did not request this, you can ignore the message.",
                    function ($message) use ($email) {
                        $message->to($email)->subject('Confirm your African Ranked newsletter subscription');
                    }
                );
            } catch (Throwable $exception) {
                report($exception);
                DB::table('newsletter_subscribers')
                    ->where('id', $pending['id'])
                    ->where('verification_token_hash', $pending['digest'])
                    ->update(['verification_token_hash' => null, 'verification_expires_at' => null,
                        'verification_sent_at' => null, 'updated_at' => now()]);

                return back()->withErrors(['newsletter' => 'We could not send the confirmation email. Please try again shortly.']);
            }
        }

        // The same response for existing addresses prevents membership enumeration.
        return back()->with('newsletter_status', 'If this address is eligible, check your inbox for a confirmation link.');
    }
}
