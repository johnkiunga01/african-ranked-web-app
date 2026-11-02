<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_is_persisted_normalized_and_deduplicated(): void
    {
        $data = ['email' => 'Reader@Example.com', 'region' => 'ke', 'consent' => '1', 'website' => ''];
        $this->from('/ke')->post('/newsletter', $data)->assertRedirect('/ke')->assertSessionHas('newsletter_status');
        $this->post('/newsletter', $data)->assertRedirect()->assertSessionHas('newsletter_status');
        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com', 'region' => 'ke']);
        $this->assertNotNull(DB::table('newsletter_subscribers')->value('consented_at'));
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
}
