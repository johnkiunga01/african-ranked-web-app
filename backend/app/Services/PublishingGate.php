<?php

namespace App\Services;

use App\Enums\ArticleType;
use App\Models\Article;

/**
 * Every rule that must pass before an article can be published.
 * evaluate() returns a list of human-readable failures (empty = pass).
 */
class PublishingGate
{
    public function __construct(private SimilarityChecker $similarity) {}

    /** @return string[] */
    public function evaluate(Article $a): array
    {
        $cfg = config('africaranked.gates');
        $fail = [];

        // 1. Unique body length
        $words = Article::countWords($a->body);
        if ($words < $cfg['min_words']) {
            $fail[] = "Body has {$words} words; minimum is {$cfg['min_words']} (FAQ, key facts and sources do not count).";
        }

        // 2. Answer block
        $ab = str_word_count(strip_tags((string) $a->answer_block));
        if ($ab < $cfg['answer_block_min'] || $ab > $cfg['answer_block_max']) {
            $fail[] = "Answer block must be {$cfg['answer_block_min']}-{$cfg['answer_block_max']} words (currently {$ab}).";
        }

        // 3. Key facts table
        $facts = $a->key_facts ?? [];
        $validFacts = array_filter($facts, fn ($f) => ! empty($f['label']) && ! empty($f['value']) && ! empty($f['verified_on']));
        if (! $facts || count($validFacts) !== count($facts)) {
            $fail[] = 'Key facts table is missing or has rows without label, value and verified_on date.';
        }

        // 4. People
        if (! $a->author || blank($a->author->bio)) {
            $fail[] = 'A named author with a bio is required.';
        }
        if (! $a->reviewer || ! $a->reviewer->is_reviewer) {
            $fail[] = 'A named reviewer (author flagged is_reviewer) is required.';
        }

        // 5. Sources and entities
        if ($a->sources()->count() < $cfg['min_sources']) {
            $fail[] = "At least {$cfg['min_sources']} cited sources are required.";
        }
        if ($a->entities()->count() < 1) {
            $fail[] = 'Link at least one entity (what the article is about).';
        }

        // 6. FAQ
        $faq = array_filter($a->faq ?? [], fn ($q) => ! empty($q['question']) && ! empty($q['answer']));
        if (count($faq) < $cfg['min_faq']) {
            $fail[] = "At least {$cfg['min_faq']} complete FAQ entries are required.";
        }

        // 7. Ranking-specific rules
        if ($a->type === ArticleType::Ranking) {
            if (blank($a->methodology_note)) {
                $fail[] = 'Rankings need a methodology note.';
            }

            $items = $a->items()->get();
            if ($items->count() < $cfg['min_ranking_items']) {
                $fail[] = "Rankings need at least {$cfg['min_ranking_items']} scored items.";
            }

            // "Top 10" in the title must really contain 10 fully written items.
            if (preg_match('/\btop\s+(\d+)\b/i', $a->title, $m) && $items->count() !== (int) $m[1]) {
                $fail[] = "Title promises Top {$m[1]} but has {$items->count()} ranked items.";
            }

            foreach ($items as $item) {
                $label = $item->entity?->name ?? "position {$item->position}";
                if (empty($item->scores) || $item->score_total === null) {
                    $fail[] = "Ranking item '{$label}' has no criteria scores.";
                }
                if (str_word_count((string) $item->blurb) < $cfg['min_item_blurb_words']) {
                    $fail[] = "Ranking item '{$label}' needs at least {$cfg['min_item_blurb_words']} words of write-up.";
                }
            }
        }

        // 8. Monetization disclosure
        if ($a->type->requiresDisclosure() && blank($a->disclosure)) {
            $fail[] = 'Sponsored and affiliate articles need a visible disclosure.';
        }
        if ($a->type === ArticleType::Sponsored && blank($a->sponsor_name)) {
            $fail[] = 'Sponsored articles need a sponsor name.';
        }

        // 9. No placeholders or bracketed blanks
        $haystack = $this->visibleText($a);
        foreach ($cfg['placeholder_patterns'] as $pattern) {
            if (preg_match($pattern, $haystack, $m)) {
                $fail[] = "Placeholder text found: '{$m[0]}'. Remove it or leave out the unverified claim.";
            }
        }

        // 10. Unverified claims flagged by the AI agent
        $pending = $a->ai_meta['needs_verification'] ?? [];
        if ($pending) {
            $fail[] = count($pending).' claim(s) still flagged needs_verification by the agent.';
        }

        // 11. Near-duplicate guard (regional copies of a canonical article are exempt)
        if (! $a->canonical_article_id) {
            $sim = $this->similarity->highestSimilarity($a);
            if ($sim > $cfg['max_similarity']) {
                $fail[] = "Too similar to an existing article (similarity {$sim}, max {$cfg['max_similarity']}).";
            }
        }

        return array_values(array_unique($fail));
    }

    /** All reader-visible text with markdown links/images removed so [text](url) isn't flagged as a blank. */
    private function visibleText(Article $a): string
    {
        $parts = [
            $a->title, $a->dek, $a->answer_block, $a->body, $a->methodology_note,
            collect($a->faq ?? [])->map(fn ($q) => ($q['question'] ?? '').' '.($q['answer'] ?? ''))->implode(' '),
            collect($a->key_facts ?? [])->map(fn ($f) => ($f['label'] ?? '').' '.($f['value'] ?? ''))->implode(' '),
            $a->items()->pluck('blurb')->implode(' '),
            $a->disclosure, $a->sponsor_name,
        ];

        $text = implode("\n", array_map('strval', $parts));
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $text);

        return $text;
    }
}
