<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

/** One controlled owner-inbox test; never enables public signup or sends campaigns. */
class NewsletterOperatorTestController extends Controller
{
    private function authorizeActor(Request $request): User
    {
        abort_unless(config('editorial.newsletter_operator_test_enabled'), 404);
        $actor = $request->attributes->get('editorial_actor');
        Gate::forUser($actor)->authorize('manage-staff');
        abort_unless(strcasecmp($actor->email, 'johnkiunga01@gmail.com') === 0, 403);

        return $actor;
    }

    public function start(Request $request, NewsletterController $newsletter, Catalog $catalog)
    {
        $actor = $this->authorizeActor($request);
        $request->validate(['confirm_test' => ['accepted']]);
        $address = 'johnkiunga01+ar-newsletter-qa-'.now()->format('Ymd').'@gmail.com';
        abort_unless(Cache::add('newsletter-operator-test:'.hash('sha256', $address), true, now()->addDay()), 429);
        $accepted = false;
        $confirmation = null;
        Event::listen(MessageSent::class, function (MessageSent $event) use ($address, &$accepted, &$confirmation) {
            if (collect($event->message->getTo())->contains(fn ($to) => $to->getAddress() === $address)) {
                $accepted = true;
                $pattern = '~'.preg_quote(rtrim(config('app.url'), '/'), '~').'/newsletter/confirm/\d+/[a-f0-9]{64}~';
                if (preg_match($pattern, $event->message->getTextBody() ?? '', $match)) {
                    $confirmation = $match[0];
                }
            }
        });
        $enabled = config('editorial.newsletter_enabled');
        try {
            config(['editorial.newsletter_enabled' => true]);
            $request->replace(['email' => $address, 'region' => 'global', 'consent' => '1', 'website' => '']);
            $newsletter($request, $catalog);
        } finally {
            config(['editorial.newsletter_enabled' => $enabled]);
        }
        $record = DB::table('newsletter_subscribers')->where('email', $address)->firstOrFail();
        AuditEvent::record($actor, 'newsletter.operator_test_requested', $actor, ['subscriber_id' => $record->id, 'transport_accepted' => $accepted]);

        return response()->json(['subscriber_id' => $record->id, 'test_recipient' => $address,
            'mail_transport_accepted' => $accepted, 'confirmation_url' => $confirmation,
            'verified_at' => $record->verified_at, 'expires_at' => $record->verification_expires_at,
            'public_signup_enabled' => (bool) config('editorial.newsletter_enabled'),
            'inbox_receipt' => 'Must be confirmed separately; SMTP acceptance is not inbox-delivery proof.']);
    }

    public function state(Request $request, int $subscriber)
    {
        $this->authorizeActor($request);
        $record = DB::table('newsletter_subscribers')->where('id', $subscriber)->firstOrFail();
        abort_unless(preg_match('/^johnkiunga01\+ar-newsletter-qa-\d{8}@gmail\.com$/D', $record->email), 404);

        return response()->json(['subscriber_id' => $record->id, 'consented_at' => $record->consented_at,
            'verified_at' => $record->verified_at, 'unsubscribed_at' => $record->unsubscribed_at,
            'eligible' => $record->verified_at !== null && $record->unsubscribed_at === null,
            'unsubscribe_url' => URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $record->id])]);
    }
}
