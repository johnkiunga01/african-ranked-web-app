<?php

namespace Tests\Feature;

use App\Support\ResponsiveEditorialImage;
use Tests\TestCase;

class ResponsiveEditorialImageTest extends TestCase
{
    public function test_responsive_candidates_preserve_transformations_and_replace_width(): void
    {
        $srcset = ResponsiveEditorialImage::srcset('https://images.pexels.com/photos/123/photo.jpeg?auto=compress&w=1400&fit=crop');
        $this->assertCount(4, explode(', ', $srcset));
        $this->assertStringContainsString('auto=compress&w=480&fit=crop 480w', $srcset);
        $this->assertStringContainsString('w=1400&fit=crop 1400w', $srcset);
        foreach ([null, 'https://africanranked.com/editorial-art/example/cover.svg', 'https://images.pexels.com.evil.test/photo.jpeg', 'https://user@images.pexels.com/photo.jpeg', 'http://images.pexels.com/photo.jpeg'] as $url) {
            $this->assertNull(ResponsiveEditorialImage::srcset($url));
        }
    }
}
