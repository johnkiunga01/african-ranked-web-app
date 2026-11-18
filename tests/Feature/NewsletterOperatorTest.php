<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NewsletterOperatorTest extends TestCase
{
    use RefreshDatabase;

    private function authorizeOperator(): void
    {
        $actor = User::factory()->create(['role' => StaffRole::SuperAdmin, 'email' => 'johnkiunga01@gmail.com']);
        config(['editorial.integration_enabled' => true, 'editorial.integration_actor_email' => $actor->email,
            'editorial.integration_token_sha256' => hash('sha256', str_repeat('x', 64)),
            'editorial.newsletter_enabled' => false, 'mail.default' => 'array']);
        $this->withHeader('Authorization', 'Bearer '.str_repeat('x', 64));
    }

    public function test_operator_test_is_disabled_by_default_and_does_not_open_public_registration(): void
    {
        $this->authorizeOperator();
        $this->postJson('/api/launch-tests/v1/newsletter', ['confirm_test' => 1])->assertNotFound();
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_one_owner_alias_can_complete_the_live_workflow_without_enabling_public_signup(): void
    {
        $this->authorizeOperator();
        config(['editorial.newsletter_operator_test_enabled' => true]);
        $r = $this->postJson('/api/launch-tests/v1/newsletter', ['confirm_test' => 1, 'email' => 'unrelated@example.test'])
            ->assertOk()->assertJsonPath('mail_transport_accepted', true)->assertJsonPath('public_signup_enabled', false)
            ->assertJsonPath('verified_at', null);
        $this->assertStringStartsWith('johnkiunga01+ar-newsletter-qa-', $r->json('test_recipient'));
        $url = $r->json('confirmation_url');
        $this->assertNotNull($url);
        $id = $r->json('subscriber_id');
        $this->get($url)->assertOk();
        $this->getJson('/api/launch-tests/v1/newsletter/'.$id)->assertJsonPath('eligible', false);
        $this->post($url)->assertRedirect();
        $state = $this->getJson('/api/launch-tests/v1/newsletter/'.$id)->assertJsonPath('eligible', true);
        $this->post($url)->assertNotFound();
        $this->get($state->json('unsubscribe_url'))->assertOk();
        $this->getJson('/api/launch-tests/v1/newsletter/'.$id)->assertJsonPath('eligible', true);
        $signed = URL::signedRoute('newsletter.unsubscribe.submit', ['subscriber' => $id]);
        $this->post($signed)->assertRedirect();
        $this->getJson('/api/launch-tests/v1/newsletter/'.$id)->assertJsonPath('eligible', false);
        $this->postJson('/api/launch-tests/v1/newsletter', ['confirm_test' => 1])->assertStatus(429);
        $this->assertFalse(config('editorial.newsletter_enabled'));
        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'unrelated@example.test']);
    }

    public function test_operator_cannot_read_or_unsubscribe_existing_real_subscribers(): void
    {
        $this->authorizeOperator();
        config(['editorial.newsletter_operator_test_enabled' => true]);
        $id = DB::table('newsletter_subscribers')->insertGetId(['email' => 'reader@example.test', 'region' => 'global', 'consented_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/launch-tests/v1/newsletter/'.$id)->assertNotFound();
    }
}
