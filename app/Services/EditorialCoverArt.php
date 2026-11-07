<?php

namespace App\Services;

/**
 * Original, article-specific editorial illustration.
 * These abstract SVGs are artwork, never documentary wildlife photography.
 */
class EditorialCoverArt
{
    public function render(string $slug, string $variant, string $title, string $category): string
    {
        $palettes = [
            'wildlife' => ['#fbbf24', '#d97706', '#713f12'],
            'travel' => ['#67e8f9', '#0891b2', '#164e63'],
            'food' => ['#fdba74', '#ea580c', '#7c2d12'],
            'music' => ['#f9a8d4', '#db2777', '#831843'],
            'business' => ['#86efac', '#16a34a', '#14532d'],
            'tech' => ['#93c5fd', '#2563eb', '#1e3a8a'],
        ];
        [$light, $mid, $deep] = $palettes[$category] ?? $palettes['travel'];
        $seed = hash('sha256', $slug.'-'.$variant);
        $first = hexdec(substr($seed, 0, 3));
        $second = hexdec(substr($seed, 3, 3));
        $third = hexdec(substr($seed, 6, 3));
        $sunX = 800 + $first % 230;
        $sunY = 115 + $second % 125;
        $hill = 340 + $third % 130;
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $safeCategory = htmlspecialchars(strtoupper(str_replace('-', ' & ', $category)), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $words = preg_split('/\s+/u', trim($title)) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $trial = trim($line.' '.$word);
            if (mb_strlen($trial) > 25 && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $trial;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        $lines = array_slice($lines, 0, 4);
        $font = count($lines) >= 4 ? 49 : (count($lines) >= 3 ? 56 : 65);
        $y = count($lines) >= 4 ? 240 : 275;
        $textLines = '';
        foreach ($lines as $text) {
            $textLines .= '<text x="78" y="'.$y.'" font-size="'.$font.'" font-weight="760" letter-spacing="-1.8" fill="#fff">'.htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8').'</text>';
            $y += $font + 15;
        }
        $pattern = '';
        for ($i = 0; $i < 8; $i++) {
            $part = hexdec(substr($seed, ($i * 3) % 55, 3));
            $cx = 760 + ($part * 37) % 420;
            $cy = 100 + ($part * 71) % 480;
            $radius = 20 + ($part % 82);
            $pattern .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$radius.'" fill="none" stroke="'.$light.'" stroke-width="1.5" opacity=".13"/>';
        }
        $visual = match ($category) {
            'music' => '<circle cx="957" cy="377" r="183" fill="#070d18" stroke="'.$light.'" stroke-width="5" opacity=".9"/><circle cx="957" cy="377" r="115" fill="none" stroke="'.$light.'" stroke-width="2" opacity=".5"/><circle cx="957" cy="377" r="46" fill="'.$light.'" opacity=".8"/><circle cx="957" cy="377" r="12" fill="#0e1420"/><path d="M830 115v175l-38 14v-179l124-30v178l-40 14" fill="none" stroke="'.$light.'" stroke-width="7" stroke-linecap="round" opacity=".7"/>',
            'tech' => '<g stroke="'.$light.'" stroke-width="4" fill="none" opacity=".7"><rect x="840" y="216" width="250" height="250" rx="30"/><rect x="900" y="278" width="126" height="128" rx="14"/><path d="M895 177v39M954 177v39M1013 177v39M895 466v39M954 466v39M1013 466v39M802 270h38M802 340h38M802 410h38M1090 270h40M1090 340h40M1090 410h40"/></g>',
            'business' => '<g fill="'.$light.'" opacity=".22"><rect x="840" y="284" width="64" height="250" rx="6"/><rect x="915" y="238" width="63" height="296" rx="6"/><rect x="990" y="314" width="67" height="220" rx="6"/><rect x="1068" y="179" width="64" height="355" rx="6"/></g><path d="M833 345l90-99 93 32 102-124" stroke="'.$light.'" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
            'food' => '<g fill="none" stroke="'.$light.'"><circle cx="970" cy="370" r="180" stroke-width="9" opacity=".56"/><circle cx="970" cy="370" r="124" stroke-width="4" opacity=".5"/><path d="M904 398q53-85 112-10 M913 329q40-48 92 11" stroke-width="12" stroke-linecap="round"/></g><path d="M790 150v154m-22-154v102m45-102v102m-25-45h-20M1134 150q-45 54-14 124v224" stroke="'.$light.'" stroke-width="11" stroke-linecap="round" fill="none"/>',
            default => '<path d="M690 505c115-170 215-155 320-45 93-158 195-162 315 0v200H690Z" fill="'.$deep.'" opacity=".8"/><path d="M680 562c160-120 283-104 408-12 100-58 165-63 245-39v145H680Z" fill="'.$mid.'" opacity=".44"/><path d="M975 502v-131m-69 60q70-72 140 0m-115 24q48-54 96 0" stroke="'.$light.'" stroke-width="7" stroke-linecap="round" fill="none" opacity=".75"/>',
        };

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675" role="img" aria-label="'.$safeTitle.' — original African Ranked illustration">'
            .'<title>'.$safeTitle.' — editorial artwork</title>'
            .'<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#131a22"/><stop offset=".6" stop-color="#1d2230"/><stop offset="1" stop-color="'.$deep.'"/></linearGradient>'
            .'<linearGradient id="wash" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#10141a"/><stop offset="1" stop-color="#10141a" stop-opacity="0"/></linearGradient></defs>'
            .'<rect width="1200" height="675" fill="url(#bg)"/>'
            .'<circle cx="'.$sunX.'" cy="'.$sunY.'" r="170" fill="'.$mid.'" opacity=".16"/>'
            .$pattern.$visual.'<rect x="0" y="0" width="735" height="675" fill="url(#wash)"/>'
            .'<rect x="80" y="78" width="55" height="7" rx="3.5" fill="'.$light.'"/>'
            .'<text x="150" y="88" font-family="Arial,Helvetica,sans-serif" font-size="20" font-weight="700" fill="'.$light.'" letter-spacing="2.6">AFRICAN RANKED</text>'
            .'<text x="80" y="160" font-family="Arial,Helvetica,sans-serif" font-size="18" font-weight="700" letter-spacing="3" fill="'.$light.'">'.$safeCategory.'</text>'
            .'<g font-family="Arial,Helvetica,sans-serif">'.$textLines.'</g>'
            .'<rect x="80" y="590" width="480" height="1" fill="#ffffff" opacity=".2"/>'
            .'<text x="80" y="622" font-family="Arial,Helvetica,sans-serif" font-size="15" fill="#d6d3d1">ORIGINAL EDITORIAL ILLUSTRATION • NOT A PHOTOGRAPH</text>'
            .'</svg>';
    }
}
