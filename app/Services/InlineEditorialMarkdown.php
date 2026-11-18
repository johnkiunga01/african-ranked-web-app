<?php

namespace App\Services;

/** Render only the small safe inline Markdown vocabulary used by CMS copy. */
class InlineEditorialMarkdown
{
    public function render(string $text): string
    {
        $parts = preg_split('~(\[[^\]\r\n]+\]\((?:https://[^\s)]+|/(?:articles|categories)/[a-z0-9-]+(?:#[a-z0-9-]+)?)\)|\*\*[^*\r\n]+\*\*)~u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';

        foreach ($parts ?: [] as $part) {
            if (preg_match('~^\[([^\]\r\n]+)\]\((https://[^\s)]+|/(?:articles|categories)/[a-z0-9-]+(?:#[a-z0-9-]+)?)\)$~u', $part, $link)
                && (str_starts_with($link[2], '/') || filter_var($link[2], FILTER_VALIDATE_URL))) {
                $html .= '<a href="'.e($link[2]).'" class="font-semibold text-amber-800 underline underline-offset-4" rel="noopener noreferrer">'.e($link[1]).'</a>';
            } elseif (preg_match('/^\*\*([^*\r\n]+)\*\*$/u', $part, $bold)) {
                $html .= '<strong>'.e($bold[1]).'</strong>';
            } else {
                $html .= e($part);
            }
        }

        return $html;
    }
}
