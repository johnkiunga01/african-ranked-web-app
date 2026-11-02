#!/usr/bin/env python3
"""Read-only HTTPS acceptance. Never signs in, writes content or calls an AI provider."""
import argparse
import datetime
import json
import re
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('origin', help='The existing staging HTTPS origin, without a path')
args = parser.parse_args()
parsed = urllib.parse.urlsplit(args.origin)
if parsed.scheme != 'https' or not parsed.hostname or parsed.username or parsed.password or parsed.path not in ('', '/') or parsed.query or parsed.fragment:
    parser.error('Provide an HTTPS origin without credentials, path, query or fragment.')
origin = args.origin.rstrip('/')
checks = []

def add(name, passed, detail):
    checks.append(dict(name=name, passed=bool(passed), detail=detail))

def read(path):
    request = urllib.request.Request(origin + path, headers={'User-Agent': 'AfricaRankedReadiness/1.0'})
    try:
        with urllib.request.urlopen(request, timeout=25) as response:
            # Bound public response sizes and never emit cookies, CSRF tokens or raw form bodies.
            return response.status, dict(response.headers), response.read(2_000_001).decode('utf-8', errors='replace'), response.url
    except urllib.error.HTTPError as error:
        return error.code, dict(error.headers), '', error.url

for path in ['/', '/up', '/staff/login', '/robots.txt', '/sitemap.xml', '/feed.xml', '/llms.txt']:
    try:
        status, headers, body, final = read(path)
        add(path + ' responds', status == 200 and len(body) <= 2_000_000, f'HTTP {status}; bounded public response inspected.')
        tag = next((value for key, value in headers.items() if key.lower() == 'x-robots-tag'), '')
        add(path + ' stays noindex', 'noindex' in tag.lower(), tag or 'No X-Robots-Tag header observed.')
        if path == '/staff/login':
            add('Real staff sign-in form', 'name="_token"' in body and 'type="password"' in body, 'Form structure checked; no credentials submitted.')
        if path == '/robots.txt':
            directives = [line.strip() for line in body.splitlines()]
            add('Staging robots blocks crawling', 'Disallow: /' in directives and 'Allow: /' not in directives, 'Blanket robots disallow must accompany preview headers.')
        if path == '/sitemap.xml':
            try:
                document = ET.fromstring(body)
                urls = [node.text for node in document.iter() if node.tag.endswith('loc')]
                add('Sitemap XML parses', True, f'{len(urls)} discovery URLs; factual/editorial acceptance is a separate gate.')
            except ET.ParseError:
                add('Sitemap XML parses', False, 'Response is not valid XML.')
        if path == '/':
            assets = re.findall(r'(?:href|src)="([^\"]*build/assets/[^\"]+)"', body)
            local = [urllib.parse.urlsplit(urllib.parse.urljoin(origin, url)) for url in assets]
            local = [url for url in local if url.netloc == parsed.netloc]
            asset_ok = bool(local)
            for asset in local[:3]:
                asset_status, _, _, _ = read(asset.path)
                asset_ok &= asset_status == 200
            add('Versioned first-party assets respond', asset_ok, f'{min(len(local), 3)} assets checked without changing files.')
    except Exception:
        add(path + ' responds', False, 'Request failed; upstream response details are not exposed.')
try:
    status, _, _, final = read('/staff')
    add('Anonymous staff access redirects to login', status == 200 and urllib.parse.urlsplit(final).path == '/staff/login', 'Read-only anonymous request; no account or session mutation requested.')
    status, _, _, _ = read('/definitely-missing-readiness-check')
    add('Missing pages return a real 404', status == 404, f'HTTP {status}')
except Exception:
    add('Anonymous routing checks', False, 'Could not inspect the public routes.')
report = dict(origin=origin, observed_at=datetime.datetime.now(datetime.timezone.utc).isoformat(), read_only=True,
              checks=checks, passed=sum(check['passed'] for check in checks), total=len(checks),
              not_proven=['Owner invitation/password setup and MFA', 'Author sign-in and permissions on the live database',
                          'Mail delivery and recovery inbox checks', 'Verified content, bylines and media rights',
                          'Database/media restore rehearsal', 'Queue processing with a real owner-approved request',
                          'Founder acceptance and production launch'])
print(json.dumps(report, indent=2))
raise SystemExit(0 if all(check['passed'] for check in checks) else 1)
