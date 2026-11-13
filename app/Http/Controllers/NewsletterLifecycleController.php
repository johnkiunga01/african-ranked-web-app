<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class NewsletterLifecycleController extends Controller
{
    public function confirmForm(int $subscriber, string $token)
    {
        abort_unless($this->validPending($subscriber, $token), 404);

        return view('pages.newsletter-lifecycle', [
            'title' => 'Confirm newsletter subscription',
            'message' => 'Confirm that you want to receive African Ranked newsletter emails.',
            'action' => route('newsletter.confirm.submit', compact('subscriber', 'token')),
            'button' => 'Confirm subscription',
        ]);
    }

    public function confirm(int $subscriber, string $token): RedirectResponse
    {
        $digest = hash('sha256', $token);
        $affected = DB::table('newsletter_subscribers')
            ->where('id', $subscriber)->where('verification_token_hash', $digest)
            ->where('verification_expires_at', '>', now())
            ->whereNull('verified_at')
            ->update([
                'verified_at' => now(), 'unsubscribed_at' => null,
                'verification_token_hash' => null, 'verification_expires_at' => null,
                'verification_sent_at' => null, 'updated_at' => now(),
            ]);
        abort_unless($affected === 1, 404);

        // Subscription is active only after this explicit POST; do not mail campaigns here.
        return redirect('/')->with('newsletter_status', 'Your African Ranked newsletter subscription is confirmed.');
    }

    public function unsubscribeForm(int $subscriber)
    {
        abort_unless(DB::table('newsletter_subscribers')->where('id', $subscriber)->exists(), 404);

        return view('pages.newsletter-lifecycle', [
            'title' => 'Unsubscribe from African Ranked',
            'message' => 'Confirm you no longer wish to receive African Ranked newsletters.',
            'action' => URL::signedRoute('newsletter.unsubscribe.submit', ['subscriber' => $subscriber]),
            'button' => 'Unsubscribe',
        ]);
    }

    public function unsubscribe(int $subscriber): RedirectResponse
    {
        DB::table('newsletter_subscribers')->where('id', $subscriber)->update([
            'unsubscribed_at' => now(), 'verified_at' => null,
            'verification_token_hash' => null, 'verification_expires_at' => null,
            'verification_sent_at' => null, 'updated_at' => now(),
        ]);

        return redirect('/')->with('newsletter_status', 'You have unsubscribed. No further newsletter messages will be sent.');
    }

    private function validPending(int $subscriber, string $token): bool
    {
        return DB::table('newsletter_subscribers')
            ->where('id', $subscriber)
            ->where('verification_token_hash', hash('sha256', $token))
            ->where('verification_expires_at', '>', now())
            ->whereNull('verified_at')->exists();
    }
}
