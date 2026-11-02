@php echo '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'; @endphp
<rss version="2.0"><channel>
<title>African Ranked</title><link>{{ $catalog->url() }}</link><description>The best of Africa, ranked.</description><language>en</language>
@foreach($articles as $item)
<item><title>{{ $item['title'] }}</title><link>{{ $catalog->url('/articles/'.$item['slug']) }}</link><guid isPermaLink="true">{{ $catalog->url('/articles/'.$item['slug']) }}</guid><description>{{ $item['excerpt'] }}</description><pubDate>{{ \Carbon\Carbon::parse($item['datePublished'])->toRfc2822String() }}</pubDate></item>
@endforeach
</channel></rss>
