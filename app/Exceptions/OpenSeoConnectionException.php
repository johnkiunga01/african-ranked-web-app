<?php

namespace App\Exceptions;

use RuntimeException;

class OpenSeoConnectionException extends RuntimeException
{
    public static function forReason(string $reason): self
    {
        return new self(match ($reason) {
            'authentication' => 'OpenSEO rejected the credential (HTTP 401). Replace the key in Secure provider credentials, then verify again.',
            'access' => 'OpenSEO denied access (HTTP 403). Check the key workspace/scopes and whether this server is allowed to reach the endpoint.',
            'rate_limit' => 'OpenSEO is rate limiting verification (HTTP 429). Wait before trying again; the credential was not changed.',
            'provider' => 'The OpenSEO endpoint is temporarily unavailable. Try verification again later; the saved credential was not changed.',
            'network' => 'This server could not complete the HTTPS connection to OpenSEO. Check outbound access, proxy/TLS configuration and provider availability.',
            'tool' => 'OpenSEO completed the handshake but refused an account/project read. Check the key workspace/scopes and account status.',
            'missing_tools' => 'OpenSEO responded but did not expose the required whoami and list_projects tools. Check endpoint compatibility and account scopes.',
            default => 'OpenSEO returned an incompatible or incomplete MCP response. Verification has not been confirmed; retry or check provider compatibility.',
        });
    }
}
