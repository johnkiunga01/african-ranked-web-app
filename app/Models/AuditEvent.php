<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function label(): string
    {
        return match ($this->action) {
            'search_report.imported' => 'Search Console report imported', 'openseo.verified' => 'OpenSEO connection verified',
            'article.onsite_optimized' => 'Search metadata defaults applied in draft',
            'credential.updated' => 'Provider credential updated', 'credential.removed' => 'Provider credential removed',
            'site.settings_updated' => 'Website settings updated', 'site.region_updated' => 'Regional edition updated',
            'site.page_saved' => 'Page draft saved', 'site.page_publish' => 'Page published', 'site.page_unpublish' => 'Page withdrawn',
            'article.promotion_prepared' => 'Article sharing copy prepared',
            'article.seo_autofixed' => 'SEO fixes saved in draft', 'article.trashed' => 'Article moved to Trash', 'article.untrashed' => 'Article restored from Trash',
            'article.ai_drafted' => 'AI-assisted private draft generated',
            'article.restored' => 'Historical content restored as new draft',
            'article.imported' => 'Repository article imported as private draft', 'article.assigned' => 'Article assignment updated',
            'growth.plan_updated' => 'Growth integration plan updated',
            'staff.mfa_operator_reset' => 'Authenticator reset by trusted server operator',
            'staff.mfa_setup_started' => 'Authenticator setup started', 'staff.mfa_enabled' => 'Two-factor authentication enabled',
            'staff.mfa_recovery_used' => 'Recovery code used', 'staff.mfa_recovery_regenerated' => 'Recovery codes regenerated', 'staff.mfa_disabled' => 'Two-factor authentication disabled',
            'staff.login' => 'Staff member signed in', 'staff.access_changed' => 'Team access updated',
            'staff.password_reset' => 'Password reset completed', 'invitation.created' => 'Staff invitation created',
            'invitation.accepted' => 'Staff invitation accepted', 'invitation.revoked' => 'Staff invitation revoked',
            'invitation.delivery_failed' => 'Invitation delivery failed', 'article.media_uploaded' => 'Editorial image uploaded', 'article.media_duplicate_rejected' => 'Duplicate image rejected',
            'article.draft_saved' => 'Draft revision saved',
            'article.submit' => 'Article submitted for review', 'article.request_changes' => 'Changes requested',
            'article.approve' => 'Article revision approved', 'article.publish' => 'Article revision published',
            'article.unpublish' => 'Article withdrawn', default => 'Workspace action recorded',
        };
    }

    public static function record(?User $actor, string $action, Model $subject, array $metadata = []): void
    {
        static::create(['user_id' => $actor?->id, 'action' => $action, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(), 'metadata' => $metadata, 'created_at' => now()]);
    }
}
