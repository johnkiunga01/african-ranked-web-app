<?php

namespace App\Services;

use App\Models\ProviderCredential;
use Throwable;

class ProviderCredentials
{
    public function key(string $provider): ?string
    {
        if (! in_array($provider, ['openseo', 'openai', 'gemini'], true)) {
            return null;
        }
        if (filled(config('services.'.$provider.'.key'))) {
            return config('services.'.$provider.'.key');
        }
        try {
            return ProviderCredential::where('provider', $provider)->first()?->secret;
        } catch (Throwable) {
            // Decryption failures do not leak encrypted data or cause a false connected status.
            return null;
        }
    }
}
