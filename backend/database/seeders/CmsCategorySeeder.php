<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CmsCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('africaranked.categories') as $slug => $name) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
