<?php

namespace App\Services;

use App\Models\ArticleRevision;
use Illuminate\Validation\ValidationException;

/** Structural checks are necessary editorial controls, not independent fact verification. */
class PublicationQuality
{
    public const MIN_WORDS = 1300;

    public const MIN_SOURCES = 3;

    public const MIN_FAQS = 3;

    public function inspect(ArticleRevision $revision): array
    {
        $blocks = $revision->body ?? [];
        $text = collect($blocks)->map(fn ($b) => $b['text'] ?? implode(' ', $b['items'] ?? []))->implode("\n");
        $words = preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', strip_tags($text));
        $failures = [];
        if ($words < self::MIN_WORDS) {
            $failures[] = "The body has {$words} words; at least 1,300 are required, excluding FAQs. Expand reporting with evidence, not padding.";
        }
        $sources = [];
        foreach ($blocks as $block) {
            $url = $block['url'] ?? '';
            if (($block['type'] ?? '') === 'link' && filter_var($url, FILTER_VALIDATE_URL)
                && parse_url($url, PHP_URL_SCHEME) === 'https'
                && parse_url($url, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST)) {
                $sources[preg_replace('/#.*$/', '', $url)] = true;
            }
        }
        if (count($sources) < self::MIN_SOURCES) {
            $failures[] = 'Add at least three distinct, reader-visible HTTPS source links. Private notes and search-result snippets do not count as citations.';
        }
        $faqs = array_filter($revision->faqs ?? [], fn ($faq) => filled($faq['question'] ?? '') && filled($faq['answer'] ?? ''));
        if (count($faqs) < self::MIN_FAQS) {
            $failures[] = 'Add at least three complete, supported reader FAQs.';
        }
        $visible = implode("\n", [$revision->title, $revision->excerpt, $text, collect($faqs)->map(fn ($q) => $q['question'].' '.$q['answer'])->implode("\n")]);
        if (preg_match('/\b(?:TBD|TODO|lorem ipsum|citation needed)\b|\[(?:insert|add|verify|source needed)[^\]]*\]/iu', $visible)) {
            $failures[] = 'Remove unresolved placeholder text before publication.';
        }
        $numbered = [];
        $section = null;
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'heading' && preg_match('/^(\d+)[.)]\s+(.+)$/u', $block['text'] ?? '', $match)) {
                $section = (int) $match[1];
                if (isset($numbered[$section])) {
                    $failures[] = 'Ranking positions must be distinct.';
                }
                $numbered[$section] = ['label' => $match[2], 'text' => ''];
            } elseif (($block['type'] ?? '') === 'heading') {
                $section = null;
            } elseif ($section !== null) {
                $numbered[$section]['text'] .= ' '.($block['text'] ?? implode(' ', $block['items'] ?? []));
            }
        }
        $promised = null;
        if (preg_match('/\b(?:top|best)\s+(\d+)\b|\b(\d+)\s+(?:best|most|top)\b/iu', $revision->title, $match)) {
            $promised = (int) (($match[1] ?? '') ?: ($match[2] ?? 0));
            $positions = array_keys($numbered);
            sort($positions);
            if ($promised < 1 || $promised > 500 || $positions !== range(1, $promised)) {
                $failures[] = 'The title promises '.$promised.' entries, but the numbered sections do not cover each position exactly once.';
            }
            foreach ($numbered as $position => $item) {
                if (str_word_count(strip_tags($item['text'])) < 60) {
                    $failures[] = 'Ranking position '.$position.' needs at least 60 words of supported explanation.';
                }
            }
            if (! preg_match('/methodology|how we ranked|ranking criteria/iu', $text."\n".$revision->source_notes)) {
                $failures[] = 'Explain the ranking methodology and criteria; their presence does not establish that scores are accurate.';
            }
        }

        return ['body_words' => $words, 'source_links' => count($sources), 'complete_faqs' => count($faqs),
            'promised_items' => $promised, 'numbered_items' => count($numbered), 'failures' => array_values(array_unique($failures)),
            'verification' => 'Human source, claim, authorship and image-rights verification is still required. No URLs were fetched.'];
    }

    public function assertPublishable(ArticleRevision $revision): void
    {
        $report = $this->inspect($revision);
        if ($report['failures']) {
            throw ValidationException::withMessages(['workflow' => $report['failures']]);
        }
    }
}
