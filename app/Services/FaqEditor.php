<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class FaqEditor
{
    public function parse(string $text): array
    {
        $faqs = [];
        if (! trim($text)) {
            return [];
        }
        foreach (preg_split('/\R\s*\R/u', trim($text)) as $section) {
            $parts = preg_split('/\R/u', trim($section), 2);
            if (count($parts) !== 2 || ! trim($parts[0]) || ! trim($parts[1]) || mb_strlen($parts[0]) > 300 || mb_strlen($parts[1]) > 3000 || count($faqs) >= 20) {
                throw ValidationException::withMessages(['faqs_text' => 'Each FAQ needs a question on its first line and an answer below it. Separate FAQs with a blank line. Maximum 20 questions, 300 characters per question and 3,000 per answer.']);
            }
            $faqs[] = ['question' => trim($parts[0]), 'answer' => trim($parts[1])];
        }

        return $faqs;
    }
}
