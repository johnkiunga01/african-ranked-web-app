<section class="staff-panel mt-6">
<p class="text-xs font-semibold uppercase tracking-widest text-amber-800">{{ $service }} · Not connected</p>
<h2 class="mt-2 text-xl font-bold">Connect {{ $service }} to see live reports here</h2>
<ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-stone-600">
<li>In Google Cloud, create a service account and enable the <strong>Google Analytics Data API</strong> and the <strong>Google Search Console API</strong>. Download its JSON key.</li>
<li>Add the service account's email address as a user: a <strong>Viewer</strong> on the GA4 property, and a <strong>Restricted</strong> user on the Search Console property.</li>
<li>On the Railway web service, set <code>GOOGLE_SERVICE_ACCOUNT_JSON</code> (the key, base64-encoded), <code>GA4_PROPERTY_ID</code> (the numeric property ID, not the G- measurement ID) and <code>SEARCH_CONSOLE_SITE_URL</code> (for example <code>sc-domain:africanranked.com</code>).</li>
</ol>
@if($email)<p class="mt-4 text-sm text-stone-600">Service account in use: <code class="break-all">{{ $email }}</code></p>@endif
</section>
