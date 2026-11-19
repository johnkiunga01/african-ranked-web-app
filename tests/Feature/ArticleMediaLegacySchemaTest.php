<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArticleMediaLegacySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_media_columns_are_added_to_a_legacy_table(): void
    {
        Schema::table('article_media_assets', function ($table) {
            $table->dropColumn(['alt', 'caption', 'credit', 'rights_confirmed_at']);
        });
        $this->assertFalse(Schema::hasColumn('article_media_assets', 'alt'));

        $migration = require database_path('migrations/2026_11_18_190000_add_missing_article_media_asset_columns.php');
        $migration->up();

        foreach (['alt', 'caption', 'credit', 'rights_confirmed_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('article_media_assets', $column), $column.' should exist');
        }
    }

    public function test_migration_is_a_no_op_when_columns_already_exist(): void
    {
        $migration = require database_path('migrations/2026_11_18_190000_add_missing_article_media_asset_columns.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('article_media_assets', 'alt'));
        $this->assertTrue(Schema::hasColumn('article_media_assets', 'rights_confirmed_at'));
    }
}
