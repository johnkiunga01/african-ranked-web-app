<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ArticleDraftData
{
    public function validate(array $data): array
    {
        $validator = Validator::make(is_array($data) ? $data : [], [
            'title' => ['required', 'string', 'max:180'], 'excerpt' => ['required', 'string', 'max:500'],
            'seo_title' => ['required', 'string', 'max:180'], 'meta_description' => ['required', 'string', 'max:500'],
            'body_markdown' => ['required', 'string', 'max:150000'],
            'faqs' => ['present', 'array', 'max:20'], 'faqs.*.question' => ['required', 'string', 'max:300'], 'faqs.*.answer' => ['required', 'string', 'max:3000'],
        ]);
        if ($validator->fails()) {
            throw new RuntimeException;
        }
        $data = $validator->validated();
        $data['faqs_text'] = collect($data['faqs'])->map(fn ($faq) => preg_replace('/\R/u', ' ', $faq['question'])."\n".preg_replace('/\R/u', ' ', $faq['answer']))->implode("\n\n");
        unset($data['faqs']);

        return $data;
    }
}
