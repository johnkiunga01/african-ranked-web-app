<?php

namespace Tests\Feature;

use App\Services\MarkdownBlocks;
use App\Support\YouTubeVideo;
use Tests\TestCase;

class EditorialVideoTest extends TestCase
{
    public function test_only_explicit_valid_youtube_links_become_video_blocks(): void
    {
        $parser = app(MarkdownBlocks::class);
        $blocks = $parser->parse('[Video: A useful explanation](https://www.youtube.com/watch?v=AbCdEf123_-)');
        $this->assertSame('video', $blocks[0]['type']);
        $this->assertSame('AbCdEf123_-', YouTubeVideo::id('https://youtu.be/AbCdEf123_-'));
        foreach (['https://youtube.com.evil.test/watch?v=AbCdEf123_-', 'https://user@youtube.com/watch?v=AbCdEf123_-', 'https://www.youtube.com:8443/watch?v=AbCdEf123_-', 'http://youtube.com/watch?v=AbCdEf123_-', 'https://www.youtube.com/watch?v[]=AbCdEf123_-', 'https://youtu.be/short', 'https://youtube.com/playlist?list=AbCdEf123_-'] as $url) {
            $this->assertNull(YouTubeVideo::id($url));
            $this->assertNotSame('video', $parser->parse('[Video: Unsafe]('.$url.')')[0]['type']);
        }
    }

    public function test_video_is_a_local_placeholder_with_safe_external_fallback(): void
    {
        $block = ['type' => 'video', 'title' => '<script>not markup</script>', 'url' => 'https://youtu.be/AbCdEf123_-'];
        $html = view('components.editorial-video', compact('block'))->render();
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('Load video', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('https://www.youtube.com/watch?v=AbCdEf123_-', $html);
        $block['url'] = 'https://evil.test/watch?v=AbCdEf123_-';
        $this->assertStringNotContainsString('data-youtube-video', view('components.editorial-video', compact('block'))->render());
    }

    public function test_infographic_preserves_its_full_layout_and_reserves_space(): void
    {
        $blocks = app(MarkdownBlocks::class)->parse('![Three useful measures](https://africanranked.com/editorial-art/example/decision-guide.svg "A conceptual diagram")');
        $this->assertSame('infographic', $blocks[0]['layout']);
        $html = view('components.content-blocks', compact('blocks'))->render();
        $this->assertStringContainsString('width="600" height="750"', $html);
        $this->assertStringContainsString('object-contain', $html);
        $this->assertStringNotContainsString('aspect-[16/10]', $html);
    }

    public function test_image_captions_round_trip_and_escape_markup(): void
    {
        $blocks = app(MarkdownBlocks::class)->parse('![A useful diagram](https://example.test/diagram.svg "An original explanatory diagram")');
        $this->assertSame('An original explanatory diagram', $blocks[0]['caption']);
        $blocks[0]['caption'] = '<script>not markup</script>';
        $html = view('components.content-blocks', compact('blocks'))->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
