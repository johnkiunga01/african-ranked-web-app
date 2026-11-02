<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates the JSON the editorial AI agent returns (see agent/editorial-agent-system-prompt.md). */
class StoreAgentDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is protected by auth:sanctum + ability middleware
    }

    public function rules(): array
    {
        return [
            'status' => ['prohibited'],
            'indexable' => ['prohibited'],
            'published_at' => ['prohibited'],
            'author_id' => ['prohibited'],
            'reviewer_id' => ['prohibited'],
            'legacy_public' => ['prohibited'],
            'type' => ['required', 'in:ranking,guide'],            // agents never create sponsored/affiliate
            'slug' => ['required', 'alpha_dash', 'max:120'],
            'edition' => ['sometimes', 'in:'.implode(',', config('africaranked.editions'))],
            'category' => ['required', 'in:'.implode(',', array_keys(config('africaranked.categories')))],
            'title' => ['required', 'string', 'max:160'],
            'dek' => ['required', 'string', 'max:320'],
            'answer_block' => ['required', 'string'],
            'body' => ['required', 'string'],
            'methodology_note' => ['nullable', 'string'],

            'key_facts' => ['required', 'array', 'min:1'],
            'key_facts.*.label' => ['required', 'string'],
            'key_facts.*.value' => ['required', 'string'],
            'key_facts.*.verified_on' => ['required', 'date'],

            'faq' => ['required', 'array', 'min:3'],
            'faq.*.question' => ['required', 'string'],
            'faq.*.answer' => ['required', 'string'],

            'sources' => ['required', 'array', 'min:3'],
            'sources.*.title' => ['required', 'string'],
            'sources.*.url' => ['required', 'url'],
            'sources.*.publisher' => ['nullable', 'string'],
            'sources.*.published_at' => ['nullable', 'date'],
            'sources.*.accessed_at' => ['required', 'date'],
            'sources.*.supports' => ['nullable', 'string'],

            'entities' => ['required', 'array', 'min:1'],
            'entities.*.slug' => ['required', 'alpha_dash'],
            'entities.*.name' => ['required', 'string'],
            'entities.*.type' => ['required', 'string'],
            'entities.*.country_code' => ['nullable', 'size:2'],
            'entities.*.role' => ['required', 'in:about,mentioned'],

            'ranking_items' => ['required_if:type,ranking', 'array'],
            'ranking_items.*.entity_slug' => ['required', 'alpha_dash'],
            'ranking_items.*.position' => ['required', 'integer', 'min:1', 'distinct'],
            'ranking_items.*.scores' => ['required', 'array', 'min:1'],
            'ranking_items.*.scores.*' => ['numeric', 'between:0,10'],
            'ranking_items.*.score_total' => ['required', 'numeric', 'between:0,10'],
            'ranking_items.*.blurb' => ['required', 'string'],

            'needs_verification' => ['present', 'array'],
            'needs_verification.*.claim' => ['required', 'string'],
            'needs_verification.*.reason' => ['required', 'string'],
            'agent' => ['nullable', 'array'],
        ];
    }
}
