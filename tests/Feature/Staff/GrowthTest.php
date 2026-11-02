<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\GrowthPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_growth_data_or_change_plans(): void
    {
        $this->get('/staff/growth/analytics')->assertRedirect('/staff/login');
        $author = User::factory()->create();
        $this->actingAs($author);
        foreach (['seo', 'analytics', 'revenue', 'integrations'] as $section) {
            $this->get('/staff/growth/'.$section)->assertForbidden();
        }
        $this->put('/staff/growth-plans/ga4', ['stage' => 'evaluating', 'version' => 0])->assertForbidden();
        $this->assertDatabaseCount('growth_plans', 0);
        $this->get('/staff')->assertOk()->assertDontSee('Build the publication.');
    }

    public function test_admin_pages_explain_measurement_and_sample_content_without_invented_metrics(): void
    {
        $this->actingAs(User::factory()->create(['role' => StaffRole::SuperAdmin]));
        $this->get('/staff')->assertOk()->assertSee('Preview samples')->assertSee('Build the publication.');
        $this->get('/staff/growth/seo')->assertOk()->assertSee('No CMS articles yet');
        $this->get('/staff/growth/analytics')->assertOk()->assertSee('Not connected');
        $this->get('/staff/growth/revenue')->assertOk()->assertSee('No revenue source connected');
        $this->get('/staff/growth/integrations')->assertOk()->assertSee('Planning record');
        $this->get('/staff/growth/unknown')->assertNotFound();
    }

    public function test_plans_require_password_confirmation_are_audited_and_reject_stale_updates(): void
    {
        $this->actingAs(User::factory()->create(['role' => StaffRole::SuperAdmin]));
        $this->put('/staff/growth-plans/ga4', ['stage' => 'evaluating', 'version' => 0])->assertRedirect('/staff/confirm-password');
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->put('/staff/growth-plans/ga4', ['stage' => 'evaluating', 'reference' => 'G-EXAMPLE123', 'version' => 0])->assertRedirect('/staff/growth/integrations');
        $plan = GrowthPlan::firstOrFail();
        $this->assertSame('evaluating', $plan->stage);
        $this->assertDatabaseHas('audit_events', ['action' => 'growth.plan_updated']);
        $this->put('/staff/growth-plans/ga4', ['stage' => 'paused', 'version' => 0])->assertSessionHasErrors('version');
        $this->assertSame('evaluating', $plan->fresh()->stage);
        $this->put('/staff/growth-plans/ga4', ['stage' => 'connected', 'version' => $plan->version])->assertSessionHasErrors('stage');
        $this->put('/staff/growth-plans/unknown', ['stage' => 'planned', 'version' => 0])->assertNotFound();
        $this->put('/staff/growth-plans/ga4', ['stage' => 'planned', 'reference' => '<script>alert(1)</script>', 'version' => $plan->version])->assertSessionHasErrors('reference');
    }
}
