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
        return $key === 'homepage' ? ['hero_title' => 'Discover the best of Africa', 'hero_highlight' => 'through rankings and guides.', 'hero_description' => 'Explore Africa’s remarkable destinations, cuisines, music, business, technology and wildlife through researched rankings and practical guides.', 'show_featured' => true, 'featured' => []] : ['name' => 'African Ranked', 'description' => 'Discover Africa through researched rankings and guides to travel, food, culture, business, technology and wildlife.', 'contact_email' => 'info@africanranked.com', 'author_ai_enabled' => false];
    }

    public function snapshot(string $key): array
    {
        if (! Schema::hasTable('site_settings')) {
            return ['value' => $this->defaults($key), 'version' => 0];
        }
        $setting = SiteSetting::where('key', $key)->first();

        $value = array_replace($this->defaults($key), $setting?->value ?? []);
        if ($key === 'general' && blank($value['contact_email'] ?? null)) {
            // Show the owner-confirmed public contact even with a legacy blank setting.
            $value['contact_email'] = 'info@africanranked.com';
        }
        if ($key === 'homepage') {
            // Upgrade only unmodified legacy starter copy. Any custom editorial text stays intact.
            $legacy = [
                'hero_title' => 'Explore Africa’s best.',
                'hero_highlight' => 'Go beyond the list.',
                'hero_description' => 'Discover the people, places and ideas shaping Africa, with thoughtful rankings and practical guides to travel, wildlife, food, music, business and technology.',
            ];
            $older = [
                'hero_title' => 'Discover the best of Africa',
                'hero_highlight' => 'through rankings and guides.',
                'hero_description' => 'Explore Africa’s remarkable destinations, food, music, businesses, technology and wildlife through carefully researched rankings and useful guides.',
            ];
            foreach ($legacy as $field => $previous) {
                if (in_array($value[$field] ?? null, [$previous, $older[$field]], true)) {
                    $value[$field] = $this->defaults('homepage')[$field];
                }
            }
        }

        return ['value' => $value, 'version' => $setting?->version ?? 0];
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
