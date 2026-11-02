<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SiteSettings
{
    public function defaults(string $key): array
    {
        return $key === 'homepage' ? ['hero_title' => 'The best of Africa,', 'hero_highlight' => 'ranked and ranked again.', 'hero_description' => '', 'show_featured' => true, 'featured' => []] : ['name' => 'African Ranked', 'description' => 'A global directory of the best of and ranked articles about Africa.', 'contact_email' => '', 'author_ai_enabled' => false];
    }

    public function snapshot(string $key): array
    {
        if (! Schema::hasTable('site_settings')) {
            return ['value' => $this->defaults($key), 'version' => 0];
        }
        $setting = SiteSetting::where('key', $key)->first();

        return ['value' => array_replace($this->defaults($key), $setting?->value ?? []), 'version' => $setting?->version ?? 0];
    }

    public function save(string $key, int $expected, array $values, User $actor): void
    {
        DB::transaction(function () use ($key, $expected, $values, $actor) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $this->defaults($key), 'version' => 0]);
            $setting = SiteSetting::where('key', $key)->lockForUpdate()->firstOrFail();
            if ($setting->version !== $expected) {
                throw ValidationException::withMessages(['version' => 'These settings changed in another session. Reload before saving.']);
            }
            $setting->value = array_replace($this->defaults($key), $setting->value, $values);
            $setting->version++;
            $setting->updated_by = $actor->id;
            $setting->save();
            AuditEvent::record($actor, 'site.settings_updated', $setting, ['section' => $key]);
        });
    }
}
