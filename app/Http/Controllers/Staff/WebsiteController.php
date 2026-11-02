<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\Region;
use App\Services\SiteSettings;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WebsiteController extends Controller
{
    public function homepage(Request $request, SiteSettings $settings, Catalog $catalog)
    {
        $edition = $request->validate(['edition' => ['nullable', Rule::in(array_keys($catalog->regions()))]])['edition'] ?? 'global';

        return view('staff.website.homepage', ['snapshot' => $settings->snapshot('homepage'), 'edition' => $edition, 'regions' => $catalog->regions(), 'articles' => Article::whereNull('trashed_at')->with('currentRevision')->latest('updated_at')->get()]);
    }

    public function saveHomepage(Request $request, SiteSettings $settings, Catalog $catalog)
    {
        $data = $request->validate(['version' => ['required', 'integer'], 'edition' => ['required', Rule::in(array_keys($catalog->regions()))], 'featured_id' => ['nullable', 'integer', Rule::exists('articles', 'id')->whereNull('trashed_at')], 'hero_title' => ['required', 'string', 'max:140'], 'hero_highlight' => ['nullable', 'string', 'max:140'], 'hero_description' => ['nullable', 'string', 'max:500'], 'show_featured' => ['required', 'boolean']]);
        $value = $settings->snapshot('homepage')['value'];
        $featured = $value['featured'];
        $featured[$data['edition']] = $data['featured_id'] ?? null;
        $settings->save('homepage', (int) $data['version'], ['featured' => $featured, 'hero_title' => $data['hero_title'], 'hero_highlight' => $data['hero_highlight'] ?? '', 'hero_description' => $data['hero_description'] ?? '', 'show_featured' => (bool) $data['show_featured']], $request->user());

        return redirect()->route('staff.website.homepage', ['edition' => $data['edition']])->with('status', 'Homepage updated. Only a published article can appear in the public featured position.');
    }

    public function feature(Request $request, Article $article, SiteSettings $settings)
    {
        abort_if($article->trashed_at, 422);
        $data = $request->validate(['version' => ['required', 'integer'], 'revision_id' => ['required', 'integer']]);
        if ((int) $article->current_revision_id !== (int) $data['revision_id']) {
            throw ValidationException::withMessages(['article' => 'This article changed. Reload before featuring it.']);
        }
        $snapshot = $settings->snapshot('homepage');
        $featured = $snapshot['value']['featured'];
        $featured['global'] = (int) ($featured['global'] ?? 0) === $article->id ? null : $article->id;
        $settings->save('homepage', (int) $data['version'], ['featured' => $featured], $request->user());

        return back()->with('status', $featured['global'] ? 'Selected for the global homepage. A private draft appears only after reviewed publication.' : 'Featured selection cleared. The homepage will use its automatic selection.');
    }

    public function general(SiteSettings $settings)
    {
        return view('staff.website.settings', ['snapshot' => $settings->snapshot('general')]);
    }

    public function saveGeneral(Request $request, SiteSettings $settings)
    {
        $data = $request->validate(['version' => ['required', 'integer'], 'name' => ['required', 'string', 'max:80'], 'description' => ['required', 'string', 'max:500'], 'contact_email' => ['nullable', 'email', 'max:254'], 'author_ai_enabled' => ['required', 'boolean']]);
        $settings->save('general', (int) $data['version'], ['name' => $data['name'], 'description' => $data['description'], 'contact_email' => $data['contact_email'] ?? '', 'author_ai_enabled' => (bool) $data['author_ai_enabled']], $request->user());

        return back()->with('status', 'Site settings updated. API credentials and the canonical server origin were not changed.');
    }

    public function regions()
    {
        return view('staff.website.regions', ['regions' => Region::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function regionForm(?Region $region = null)
    {
        return view('staff.website.region-edit', compact('region'));
    }

    public function saveRegion(Request $request, ?Region $region = null)
    {
        $data = $request->validate([
            'code' => $region ? ['exclude'] : ['required', 'regex:/^[a-z]{2}$/D', Rule::notIn(['up']), Rule::unique('regions', 'code')],
            'name' => ['required', 'string', 'max:120'], 'country_code' => ['nullable', 'regex:/^[A-Z]{2}$/D'],
            'locale' => ['required', 'regex:/^en(?:-[A-Z]{2})?$/D', Rule::unique('regions', 'locale')->ignore($region?->id)],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/D'], 'currency_symbol' => ['required', 'string', 'max:12'],
            'flag' => ['nullable', 'string', 'max:16'], 'tagline' => ['required', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'version' => $region ? ['required', 'integer'] : ['nullable'],
            'newsletter_title' => ['required', 'string', 'max:180'], 'newsletter_subtitle' => ['required', 'string', 'max:500'],
        ]);
        if ($region?->code === 'global' && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'The global edition must stay active.']);
        }
        DB::transaction(function () use ($request, $region, $data) {
            if ($region) {
                $model = Region::lockForUpdate()->findOrFail($region->id);
                if ($model->version !== (int) $data['version']) {
                    throw ValidationException::withMessages(['version' => 'This edition changed. Reload before saving.']);
                }
            } else {
                $model = new Region(['code' => $data['code']]);
            }
            $terms = $model->localized_terms ?: app(Catalog::class)->data('regions')['global']['localizedTerms'];
            $terms['newsletterTitle'] = $data['newsletter_title'];
            $terms['newsletterSubtitle'] = $data['newsletter_subtitle'];
            foreach (['name', 'country_code', 'locale', 'currency', 'currency_symbol', 'flag', 'tagline', 'is_active', 'sort_order'] as $field) {
                $model->$field = $data[$field] ?? '';
            }
            $model->localized_terms = $terms;
            $model->popular_categories ??= [];
            $model->version = ($model->version ?? 0) + 1;
            $model->updated_by = $request->user()->id;
            $model->save();
            AuditEvent::record($request->user(), 'site.region_updated', $model);
        });

        return redirect()->route('staff.website.regions')->with('status', 'Edition saved. Active editions update navigation, routes and discovery automatically.');
    }
}
