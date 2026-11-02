<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\ArticleMediaAsset;
use App\Models\User;
use App\Services\ArticleImageReuseGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create([
            'role' => StaffRole::SuperAdmin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_editor_has_simple_photo_upload_controls(): void
    {
        $this->actingAs($this->staff())->get('/staff/articles/create')
            ->assertOk()
            ->assertSee('data-media-composer', false)
            ->assertSee('data-media-dropzone', false)
            ->assertSee('Set as cover')
            ->assertSee('Insert into article')
            ->assertSee('data-media-rights', false);
    }

    public function test_author_can_upload_once_and_duplicate_file_is_rejected(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD/WebP is required to test image conversion.');
        }
        Storage::fake('public');
        $this->actingAs($this->staff());
        $image = UploadedFile::fake()->image('original.jpg', 800, 500);
        $result = $this->postJson('/staff/article-media', [
            'file' => $image, 'rights_confirmed' => 1,
            'alt' => 'Giraffes under an acacia tree in the savanna',
            'caption' => 'A scenic illustration of a safari route',
            'credit' => 'Editorial photographer',
        ])->assertCreated()->assertJsonPath('width', 800);
        $path = 'editorial/'.$result->json('filename');
        Storage::disk('public')->assertExists($path);
        $this->assertSame('RIFF', substr(Storage::disk('public')->get($path), 0, 4));
        $this->assertSame(1, ArticleMediaAsset::count());
        $this->get('/editorial-media/'.$result->json('filename'))
            ->assertOk()->assertHeader('Content-Type', 'image/webp');
        $sameFile = UploadedFile::fake()->image('original.jpg', 800, 500);
        $this->postJson('/staff/article-media', [
            'file' => $sameFile, 'rights_confirmed' => 1,
            'alt' => 'Giraffes under an acacia tree in the savanna',
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame(1, ArticleMediaAsset::count());
    }

    public function test_upload_requires_permission_and_real_image(): void
    {
        $this->postJson('/staff/article-media', [
            'file' => UploadedFile::fake()->image('file.png', 400, 200),
            'alt' => 'Detailed image description', 'rights_confirmed' => 1,
        ])->assertUnauthorized();
        $this->actingAs($this->staff())->postJson('/staff/article-media', [
            'file' => UploadedFile::fake()->create('file.svg', 200, 'image/svg+xml'),
            'alt' => 'Detailed image description', 'rights_confirmed' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_pexels_url_variations_are_identified_as_one_photo(): void
    {
        $guard = app(ArticleImageReuseGuard::class);
        $this->assertSame(
            $guard->key('https://images.pexels.com/photos/123456/pexels-photo-123456.jpeg?w=800'),
            $guard->key('https://images.pexels.com/photos/123456/pexels-photo-123456.jpeg?w=1400')
        );
        $this->assertNotSame(
            $guard->key('https://images.pexels.com/photos/123456/pexels-photo-123456.jpeg'),
            $guard->key('https://images.pexels.com/photos/234567/pexels-photo-234567.jpeg')
        );
    }
}
