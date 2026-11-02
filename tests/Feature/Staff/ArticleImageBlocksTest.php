<?php

namespace Tests\Feature\Staff;

use App\Services\MarkdownBlocks;
use Tests\TestCase;

class ArticleImageBlocksTest extends TestCase
{
    public function test_https_images_are_parsed_as_images_with_descriptive_alt_text(): void
    {
        $blocks = app(MarkdownBlocks::class)->parse(
            '![A giraffe beside a safari vehicle](https://images.pexels.com/photos/20523197/pexels-photo-20523197.jpeg)'
        );

        $this->assertSame([
            ['type' => 'image', 'alt' => 'A giraffe beside a safari vehicle', 'url' => 'https://images.pexels.com/photos/20523197/pexels-photo-20523197.jpeg'],
        ], $blocks);
    }

    public function test_non_https_image_markup_is_not_executed(): void
    {
        $blocks = app(MarkdownBlocks::class)->parse('![Unsafe](javascript:alert(1))');

        $this->assertSame('paragraph', $blocks[0]['type']);
        $this->assertSame('![Unsafe](javascript:alert(1))', $blocks[0]['text']);
    }

    public function test_inline_photos_render_accessibly_with_attribution(): void
    {
        $blocks = [[
            'type' => 'image',
            'url' => 'https://images.pexels.com/photos/20523197/pexels-photo-20523197.jpeg',
            'alt' => 'Two giraffes crossing the road',
            'caption' => 'Wait for animals to cross.',
            'credit' => 'Zacchaeus Rains / Pexels',
            'source' => 'https://www.pexels.com/photo/giraffes-and-car-on-safari-in-africa-20523197/',
        ]];

        $html = view('components.content-blocks', compact('blocks'))->render();

        $this->assertStringContainsString('alt="Two giraffes crossing the road"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('Zacchaeus Rains / Pexels', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }
}
