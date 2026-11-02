<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleMediaAsset;
use App\Models\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleMediaController extends Controller
{
    /**
     * Images are processed into safe, metadata-free WebP and kept on the
     * production persistent volume. Upload does not publish an article.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Article::class);

        $validated = $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:8192',
                'dimensions:min_width=200,min_height=120,max_width=8000,max_height=8000'],
            'article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'alt' => ['required', 'string', 'min:8', 'max:300'],
            'caption' => ['nullable', 'string', 'max:500'],
            'credit' => ['nullable', 'string', 'max:180'],
            'rights_confirmed' => ['accepted'],
        ]);
        $article = isset($validated['article_id'])
            ? Article::findOrFail($validated['article_id'])
            : null;
        if ($article) {
            Gate::authorize('update', $article);
        }

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return response()->json(['message' => 'Image processing is unavailable. Contact an administrator.'], 503);
        }

        $uploaded = $request->file('file');
        $hash = hash_file('sha256', $uploaded->getRealPath());
        if (ArticleMediaAsset::where('sha256', $hash)->exists()) {
            throw ValidationException::withMessages(['file' => 'This exact photo has already been uploaded. Choose a different image for this post, or use the existing image within its original article.']);
        }
        [$width, $height, $type] = @getimagesize($uploaded->getRealPath()) ?: [0, 0, 0];
        if (! $width || ! $height || $width * $height > 20000000) {
            throw ValidationException::withMessages(['file' => 'Choose an image smaller than 20 megapixels.']);
        }
        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($uploaded->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($uploaded->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($uploaded->getRealPath()),
            default => false,
        };
        if (! $source) {
            throw ValidationException::withMessages(['file' => 'The image could not be decoded. Try a JPG, PNG or WebP file.']);
        }

        $scale = min(1, 2200 / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        if (! $canvas) {
            imagedestroy($source);
            return response()->json(['message' => 'Image processing failed.'], 500);
        }
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 255, 255, 255, 0));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        $ok = imagewebp($canvas, null, 83);
        $bytes = ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);
        if (! $ok || ! $bytes || strlen($bytes) > 8 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'The image could not be optimised. Try a smaller file.']);
        }

        $id = (string) Str::uuid();
        $name = $id.'.webp';
        $path = 'editorial/'.$name;
        if (! Storage::disk('public')->put($path, $bytes)) {
            return response()->json(['message' => 'Unable to save this image. Please retry.'], 503);
        }
        try {
            ArticleMediaAsset::create([
                'id' => $id, 'user_id' => $request->user()->id,
                'article_id' => $article?->id, 'sha256' => $hash,
                'storage_path' => $path, 'width' => $newWidth,
                'height' => $newHeight, 'bytes' => strlen($bytes),
                'alt' => $validated['alt'], 'caption' => $validated['caption'] ?? null,
                'credit' => $validated['credit'] ?? null, 'rights_confirmed_at' => now(),
            ]);
        } catch (\\Illuminate\\Database\\UniqueConstraintViolationException $e) {
            Storage::disk('public')->delete($path);
            throw ValidationException::withMessages(['file' => 'This image is already in the media library. Select a different photo.']);
        }
        $url = url('/editorial-media/'.$name);
        AuditEvent::record($request->user(), 'article.media_uploaded', $article ?? $request->user(), [
            'asset' => $name, 'width' => $newWidth, 'height' => $newHeight,
            'bytes' => strlen($bytes), 'article_id' => $article?->id,
        ]);

        return response()->json([
            'url' => $url, 'filename' => $name,
            'width' => $newWidth, 'height' => $newHeight, 'alt' => $validated['alt'],
            'caption' => $validated['caption'] ?? '', 'credit' => $validated['credit'] ?? '',
            'message' => 'Image uploaded. Insert it into your article or set it as the cover, then save your draft.',
        ], 201)->header('Cache-Control', 'private, no-store');
    }

    public function show(string $filename)
    {
        abort_unless(preg_match('/^[a-f0-9-]{36}\.webp$/D', $filename), 404);
        $path = 'editorial/'.$filename;
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, $filename, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
