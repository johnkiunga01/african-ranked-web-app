<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>{{ $title }} | African Ranked</title></head>
<body style="font:16px/1.6 system-ui,sans-serif;background:#fafaf9;color:#1c1917;padding:40px 20px">
<main style="max-width:560px;margin:0 auto;background:white;padding:32px;border-radius:12px">
<a href="/" style="color:#92400e">African Ranked</a>
<h1>{{ $title }}</h1><p>{{ $message }}</p>
<form action="{{ $action }}" method="POST">@csrf
<button type="submit" style="border:0;background:#fbbf24;color:#1c1917;padding:14px 24px;border-radius:9px;font-weight:bold;cursor:pointer">{{ $button }}</button></form>
</main></body></html>
