<?php

namespace App\Services;

use App\Exceptions\OpenSeoConnectionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenSeoMcp
{
    private ?string $session = null;

    private int $id = 0;

    private string $protocol = '2025-03-26';

    // Verification remains free. Research has a separate narrow allowlist.
    public function research(string $tool, array $arguments): array
    {
        if (! in_array($tool, ['whoami', 'list_projects', 'get_project_context', 'list_saved_keywords', 'get_keyword_metrics', 'get_serp_results', 'research_keywords'], true)) {
            throw new RuntimeException('This OpenSEO tool is not allowed for editorial research.');
        }
        try {
            $init = $this->rpc('initialize', ['protocolVersion' => $this->protocol, 'capabilities' => (object) [], 'clientInfo' => ['name' => 'african-ranked', 'version' => '1.1']]);
            $version = $init['protocolVersion'] ?? null;
            if (! in_array($version, ['2024-11-05', '2025-03-26', '2025-06-18'], true)) {
                throw new RuntimeException;
            }
            $this->protocol = $version;
            $this->rpc('notifications/initialized', null, true);
            $result = $this->rpc('tools/call', ['name' => $tool, 'arguments' => $arguments ?: (object) []]);
            if ($result['isError'] ?? false) {
                throw new RuntimeException;
            }
            if (is_array($result['structuredContent'] ?? null)) {
                return $result['structuredContent'];
            }
            foreach ($result['content'] ?? [] as $part) {
                if (($part['type'] ?? '') === 'text') {
                    $decoded = json_decode($part['text'], true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }
            throw new RuntimeException;
        } catch (Throwable) {
            throw new RuntimeException('OpenSEO research could not be completed. No research was invented and no provider retry was made. Check the integration and credit balance.');
        }
    }

    public function check(): int
    {
        if (! filled(app(ProviderCredentials::class)->key('openseo'))) {
            throw new RuntimeException('Add the OpenSEO key under Secure provider credentials, or configure OPENSEO_API_KEY on the server first.');
        }
        $this->session = null;
        try {
            $init = $this->rpc('initialize', ['protocolVersion' => $this->protocol, 'capabilities' => (object) [], 'clientInfo' => ['name' => 'african-ranked', 'version' => '1.0']]);
            $version = $init['protocolVersion'] ?? null;
            if (! in_array($version, ['2024-11-05', '2025-03-26', '2025-06-18'], true)) {
                throw new RuntimeException;
            }
            $this->protocol = $version;
            $this->rpc('notifications/initialized', null, true);
            $tools = [];
            $cursor = null;
            for ($page = 0; $page < 10; $page++) {
                $result = $this->rpc('tools/list', $cursor ? ['cursor' => $cursor] : (object) []);
                if (! is_array($result['tools'] ?? null)) {
                    throw new RuntimeException;
                }
                foreach ($result['tools'] as $tool) {
                    $tools[] = $tool['name'] ?? '';
                }
                $cursor = $result['nextCursor'] ?? null;
                if (! $cursor) {
                    break;
                }
            }
            foreach (['whoami', 'list_projects'] as $tool) {
                if (! in_array($tool, $tools, true)) {
                    throw OpenSeoConnectionException::forReason('missing_tools');
                }
                $result = $this->rpc('tools/call', ['name' => $tool, 'arguments' => (object) []]);
                if (($result['isError'] ?? false) || ! isset($result['content'])) {
                    throw OpenSeoConnectionException::forReason('tool');
                }
            }

            return count(array_unique($tools));
        } catch (OpenSeoConnectionException $exception) {
            throw $exception;
        } catch (ConnectionException) {
            throw OpenSeoConnectionException::forReason('network');
        } catch (Throwable) {
            // Never expose upstream bodies, exceptions, account data or credentials.
            throw OpenSeoConnectionException::forReason('protocol');
        }
    }

    private function rpc(string $method, array|object|null $params = null, bool $notification = false): array
    {
        $headers = ['Accept' => 'application/json, text/event-stream', 'MCP-Protocol-Version' => $this->protocol];
        if ($this->session) {
            $headers['Mcp-Session-Id'] = $this->session;
        }
        $payload = ['jsonrpc' => '2.0', 'method' => $method];
        if (! $notification) {
            $payload['id'] = ++$this->id;
        }
        if ($params !== null) {
            $payload['params'] = $params;
        }
        $response = Http::withToken(app(ProviderCredentials::class)->key('openseo'))->withHeaders($headers)
            ->connectTimeout(5)->timeout(30)->withOptions(['allow_redirects' => false, 'stream' => true])
            ->post('https://app.openseo.so/mcp', $payload);
        if (! $response->successful()) {
            throw OpenSeoConnectionException::forReason(match ($response->status()) {
                401 => 'authentication', 403 => 'access', 429 => 'rate_limit',
                500, 502, 503, 504 => 'provider', default => 'protocol',
            });
        }
        $session = $response->header('Mcp-Session-Id');
        if ($session) {
            $this->session = $session;
        }
        if ($notification) {
            return [];
        }
        $stream = $response->toPsrResponse()->getBody();
        $body = '';
        while (! $stream->eof() && strlen($body) <= 2097152) {
            $body .= $stream->read(8192);
        }
        $stream->close();
        if (strlen($body) > 2097152) {
            throw new RuntimeException;
        }
        $messages = [];
        if (str_contains($response->header('Content-Type') ?? '', 'text/event-stream')) {
            foreach (preg_split('/\r?\n\r?\n/', $body) as $event) {
                $data = [];
                foreach (preg_split('/\r?\n/', $event) as $line) {
                    if (str_starts_with($line, 'data:')) {
                        $data[] = ltrim(substr($line, 5), ' ');
                    }
                }
                if ($data) {
                    $messages[] = json_decode(implode("\n", $data), true, 512, JSON_THROW_ON_ERROR);
                }
            }
        } else {
            $messages[] = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }
        foreach ($messages as $message) {
            if (($message['id'] ?? null) === $payload['id'] && ! isset($message['error']) && is_array($message['result'] ?? null)) {
                return $message['result'];
            }
        }
        throw new RuntimeException;
    }
}
