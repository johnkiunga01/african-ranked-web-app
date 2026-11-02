<?php

namespace App\Services;

class MarkdownBlocks
{
    public function parse(string $text): array
    {
        $blocks = [];
        foreach (preg_split('/\R\s*\R/', trim($text)) as $section) {
            $lines = preg_split('/\R/', trim($section));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (preg_match('~^!\[([^\]\r\n]+)\]\((https://[^\s)]+)\)$~iu', $line, $image) && filter_var($image[2], FILTER_VALIDATE_URL)) {
                    $blocks[] = ['type' => 'image', 'alt' => $image[1], 'url' => $image[2]];
                } elseif (preg_match('~^\[([^\]\r\n]+)\]\((https://[^\s)]+|/(?:articles|categories)/[a-z0-9-]+(?:\#[a-z0-9-]+)?)\)$~iu', $line, $link) && (str_starts_with($link[2], '/') || filter_var($link[2], FILTER_VALIDATE_URL))) {
                    $blocks[] = ['type' => 'link', 'text' => $link[1], 'url' => $link[2]];
                } elseif (preg_match('/^(#{2,3})\s+(.+)$/u', $line, $match)) {
                    $blocks[] = ['type' => 'heading', 'level' => strlen($match[1]), 'text' => $match[2]];
                } elseif (preg_match('/^[-*]\s+(.+)$/u', $line, $match)) {
                    $last = array_key_last($blocks);
                    if ($last !== null && $blocks[$last]['type'] === 'list') {
                        $blocks[$last]['items'][] = $match[1];
                    } else {
                        $blocks[] = ['type' => 'list', 'items' => [$match[1]]];
                    }
                } else {
                    $blocks[] = ['type' => 'paragraph', 'text' => $line];
                }
            }
        }

        return $blocks;
    }
}
