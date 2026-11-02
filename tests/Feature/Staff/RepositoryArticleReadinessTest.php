<?php

namespace Tests\Feature\Staff;

use Tests\TestCase;

class RepositoryArticleReadinessTest extends TestCase
{
    public function test_editorial_seed_articles_are_structurally_complete(): void
    {
        $articles = json_decode(file_get_contents(base_path('content/articles.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(13, $articles);

        foreach ($articles as $article) {
            $slug = $article['slug'];
            $this->assertNotEmpty($article['title'], $slug);
            $this->assertNotEmpty($article['excerpt'], $slug);
            $this->assertSame('African Ranked Editorial Team', $article['author'], $slug);
            $this->assertNotEmpty($article['imageAlt'] ?? '', $slug);

            $sources = [];
            $numbered = [];
            $position = null;
            $excluded = false;
            $bodyText = '';

            foreach ($article['body'] as $block) {
                if ($block['type'] === 'heading' && ($block['level'] ?? 2) === 2) {
                    $heading = $block['text'] ?? '';
                    $excluded = (bool) preg_match('/^\d+[.)]\s|^(?:answer block|key facts|ranked items|sources|methodology note|frequently asked questions)$/iu', trim($heading));
                    $position = preg_match('/^(\d+)[.)]\s/', $heading, $match) ? (int) $match[1] : null;
                    if ($position !== null) {
                        $this->assertArrayNotHasKey($position, $numbered, $slug.' has repeated ranking positions');
                        $numbered[$position] = '';
                    }
                    continue;
                }

                if ($block['type'] === 'link' && str_starts_with($block['url'] ?? '', 'https://')) {
                    $sources[preg_replace('/#.*$/', '', $block['url'])] = true;
                }
                $text = $block['text'] ?? implode(' ', $block['items'] ?? []);
                if (! $excluded && $block['type'] !== 'link') {
                    $bodyText .= ' '.$text;
                }
                if ($position !== null && $block['type'] !== 'link') {
                    $numbered[$position] .= ' '.$text;
                }
            }

            $this->assertGreaterThanOrEqual(1300, $this->wordCount($bodyText), $slug.' narrative body');
            $this->assertGreaterThanOrEqual(5, count($sources), $slug.' cited sources');
            $this->assertGreaterThanOrEqual(3, count($article['faqs'] ?? []), $slug.' FAQs');

            foreach ($numbered as $position => $text) {
                $this->assertGreaterThanOrEqual(60, $this->wordCount($text), $slug.' item '.$position);
            }

            if (preg_match('/(?:top|best)\s+(\d+)|(\d+)\s+(?:best|most|top)/iu', $article['title'], $match)) {
                $promised = (int) (($match[1] ?? '') ?: ($match[2] ?? 0));
                $positions = array_keys($numbered);
                sort($positions);
                $this->assertSame(range(1, $promised), $positions, $slug.' advertised ranking');
            }
        }
    }

    private function wordCount(string $text): int
    {
        return preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', strip_tags($text));
    }
}
