<?php

namespace App\Services;

use App\Support\YouTubeVideo;

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
                if (preg_match('~^\[Video: ([^\]\r\n]+)\]\((https://[^\s)]+)\)$~iu', $line, $video)
                    && YouTubeVideo::id($video[2])) {
                    $blocks[] = ['type' => 'video', 'title' => $video[1], 'url' => $video[2]];
                } elseif (preg_match('~^!\[([^\]\r\n]+)\]\((https://[^\s)]+)(?: "([^"\r\n]+)")?\)$~iu', $line, $image) && filter_var($image[2], FILTER_VALIDATE_URL)) {
                    $block = ['type' => 'image', 'alt' => $image[1], 'url' => $image[2]];
                    if (filled($image[3] ?? null)) {
                        $block['caption'] = $image[3];
                    }
                    if (preg_match('~/editorial-art/[a-z0-9-]+/decision-guide\.svg$~', parse_url($image[2], PHP_URL_PATH) ?? '')) {
                        $block['layout'] = 'infographic';
                    }
                    $blocks[] = $block;
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
                    // Keep citation links reader-visible even when research
                    // manuscripts place Markdown links within a sentence.
                    // The body is still plain text; no untrusted HTML is rendered.
                    $links = [];
                    $plain = preg_replace_callback(
                        '~\[([^\]\r\n]+)\]\((https://[^\s)]+|/(?:articles|categories)/[a-z0-9-]+(?:\#[a-z0-9-]+)?)\)~iu',
                        function (array $match) use (&$links) {
                            if (str_starts_with($match[2], '/') || filter_var($match[2], FILTER_VALIDATE_URL)) {
                                $links[] = ['type' => 'link', 'text' => $match[1], 'url' => $match[2]];

                                return $match[1];
                            }

                            return $match[0];
                        },
                        $line
                    );
                    $plain = preg_replace('/\*\*([^*\r\n]+)\*\*/u', '$1', $plain);
                    $blocks[] = ['type' => 'paragraph', 'text' => $plain];
                    foreach ($links as $citation) {
                        $blocks[] = $citation;
                    }
                }
            }
        }

        return $blocks;
    }
}
