<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['editorial.newsletter_enabled' => true]);
        \Illuminate\Support\Facades\Mail::fake();
    }

    public function test_subscription_is_persisted_normalized_and_deduplicated(): void
    {
        $data = ['email' => 'Reader@Example.com', 'region' => 'ke', 'consent' => '1', 'website' => ''];
        $this->from('/ke')->post('/newsletter', $data)->assertRedirect('/ke')->assertSessionHas('newsletter_status');
        $this->post('/newsletter', $data)->assertRedirect()->assertSessionHas('newsletter_status');
        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com', 'region' => 'ke']);
        $this->assertNotNull(DB::table('newsletter_subscribers')->value('consented_at'));
        $this->assertNull(DB::table('newsletter_subscribers')->value('verified_at'));
        $this->assertNotNull(DB::table('newsletter_subscribers')->value('verification_token_hash'));
    }

    public function test_invalid_email_missing_consent_and_invalid_region_are_rejected(): void
    {
        $this->post('/newsletter', ['email' => 'invalid', 'region' => 'xx'])->assertSessionHasErrors(['email', 'region', 'consent']);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_honeypot_submission_does_not_store_subscriber(): void
    {
        $this->post('/newsletter', ['email' => 'bot@example.com', 'region' => 'global', 'consent' => '1', 'website' => 'spam'])->assertSessionHasErrors('website');
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_signup_is_rate_limited_and_no_public_subscriber_read_endpoint_exists(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/newsletter', ['email' => 'test@example.com', 'region' => 'global', 'consent' => '1'])->assertRedirect();
        }
        $this->post('/newsletter', ['email' => 'other@example.com', 'region' => 'global', 'consent' => '1'])->assertStatus(429);
        $this->get('/newsletter')->assertStatus(405);
        $this->get('/newsletter/subscribers')->assertNotFound();
        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }
    public function test_newsletter_signup_is_disabled_unless_explicitly_enabled(): void
    {
        config(['editorial.newsletter_enabled' => false]);
        $this->post('/newsletter', [
            'email' => 'reader@example.com', 'region' => 'global', 'consent' => '1',
        ])->assertNotFound();
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_confirmation_is_post_only_and_replays_expire(): void
    {
        $token = str_repeat('a', 64);
        $id = DB::table('newsletter_subscribers')->insertGetId([
            'email' => 'reader@example.com', 'region' => 'global',
            'consented_at' => now(), 'verification_token_hash' => hash('sha256', $token),
            'verification_expires_at' => now()->addDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $url = route('newsletter.confirm', ['subscriber' => $id, 'token' => $token]);
        $this->get($url)->assertOk()->assertSee('Confirm subscription');
        $this->assertNull(DB::table('newsletter_subscribers')->where('id', $id)->value('verified_at'));
        $this->post(route('newsletter.confirm.submit', ['subscriber' => $id, 'token' => $token]))
            ->assertRedirect('/');
        $this->assertNotNull(DB::table('newsletter_subscribers')->where('id', $id)->value('verified_at'));
        $this->post(route('newsletter.confirm.submit', ['subscriber' => $id, 'token' => $token]))
            ->assertNotFound();
    }

    public function test_unsubscribe_requires_valid_signed_url_and_explicit_post(): void
    {
        $id = DB::table('newsletter_subscribers')->insertGetId([
            'email' => 'reader@example.com', 'region' => 'global',
            'consented_at' => now(), 'verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $signed = \Illuminate\Support\Facades\URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $id]);
        $this->get($signed)->assertOk()->assertSee('Unsubscribe');
        $this->assertNull(DB::table('newsletter_subscribers')->where('id', $id)->value('unsubscribed_at'));
        $this->post(route('newsletter.unsubscribe.submit', ['subscriber' => $id]))->assertForbidden();
        $this->post(\Illuminate\Support\Facades\URL::signedRoute('newsletter.unsubscribe.submit', ['subscriber' => $id]))
            ->assertRedirect('/');
        $this->assertNotNull(DB::table('newsletter_subscribers')->where('id', $id)->value('unsubscribed_at'));
    }
}
