<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['localized_terms' => 'array', 'popular_categories' => 'array', 'is_active' => 'boolean', 'version' => 'integer'];
    }

    public function publicRecord(): array
    {
        return ['code' => $this->code, 'name' => $this->name, 'countryCode' => $this->country_code, 'language' => 'en', 'locale' => $this->locale, 'currency' => $this->currency, 'currencySymbol' => $this->currency_symbol, 'flag' => $this->flag, 'default' => $this->code === 'global', 'tagline' => $this->tagline, 'localizedTerms' => $this->localized_terms, 'popularCategories' => $this->popular_categories];
    }
}
