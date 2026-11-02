<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditorialEvidence
{
    public function fromForm(array $data): array
    {
        $data['facts'] = $this->lines($data['key_facts_text'] ?? '', ['fact', 'source_url', 'verified_at']);
        $data['sources'] = $this->lines($data['sources_text'] ?? '', ['title', 'publisher', 'url', 'accessed_at']);
        unset($data['key_facts_text'], $data['sources_text']);

        return $this->validate($data);
    }

    public function validate(array $data): array
    {
        return Validator::make($data, [
            'answer' => ['nullable', 'string', 'max:1500'],
            'content_type' => ['required', 'in:guide,ranking,data'],
            'last_verified_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'image_rights' => ['nullable', 'string', 'max:3000'],
            'methodology' => ['nullable', 'string', 'max:10000'],
            'facts' => ['present', 'array', 'max:8'],
            'facts.*.fact' => ['required', 'string', 'max:1000'],
            'facts.*.source_url' => ['required', 'url:https', 'max:2048'],
            'facts.*.verified_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sources' => ['present', 'array', 'max:40'],
            'sources.*.title' => ['required', 'string', 'max:300'],
            'sources.*.publisher' => ['required', 'string', 'max:180'],
            'sources.*.url' => ['required', 'url:https', 'max:2048'],
            'sources.*.accessed_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ])->validate();
    }

    public function inspect(?array $data): array
    {
        $failures = [];
        $data ??= [];
        $words = preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', $data['answer'] ?? '');
        if ($words < 40 || $words > 60) {
            $failures[] = 'Add a supported 40–60 word direct answer, separate from the body.';
        }
        $sources = collect($data['sources'] ?? [])->filter(fn ($s) => filled($s['title'] ?? null) && filled($s['publisher'] ?? null) && filter_var($s['url'] ?? '', FILTER_VALIDATE_URL) && str_starts_with($s['url'], 'https://') && $this->validDate($s['accessed_at'] ?? ''))->unique(fn ($s) => preg_replace('/#.*$/', '', $s['url']));
        if ($sources->count() < 5) {
            $failures[] = 'Record five distinct cited sources with publisher, HTTPS URL and actual access date.';
        }
        $urls = $sources->pluck('url')->all();
        $facts = collect($data['facts'] ?? [])->filter(fn ($f) => filled($f['fact'] ?? '') && in_array($f['source_url'] ?? '', $urls, true) && $this->validDate($f['verified_at'] ?? ''));
        if ($facts->count() < 4 || $facts->count() > 8) {
            $failures[] = 'Record four to eight dated key facts, each linked to a recorded source.';
        }
        if (! $this->validDate($data['last_verified_at'] ?? '')) {
            $failures[] = 'Record the actual last-verified date.';
        }
        if (! filled($data['image_rights'] ?? '')) {
            $failures[] = 'Record the hero image creator, permission or licence and attribution requirements.';
        }
        if (in_array($data['content_type'] ?? '', ['ranking', 'data'], true) && ! filled($data['methodology'] ?? '')) {
            $failures[] = 'Explain ranking criteria, weights, evidence and limits; numerical scores must be supported editorial judgments.';
        }

        return $failures;
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date && $date <= now()->format('Y-m-d');
    }

    private function lines(string $text, array $keys): array
    {
        $rows = [];
        foreach (preg_split('/\R/u', trim($text)) as $line) {
            if (! filled($line)) {
                continue;
            }
            $values = array_map('trim', explode('|', $line));
            if (count($values) !== count($keys)) {
                throw ValidationException::withMessages(['evidence' => 'Use the displayed pipe-separated columns on each evidence line.']);
            }
            $rows[] = array_combine($keys, $values);
        }

        return $rows;
    }
}
