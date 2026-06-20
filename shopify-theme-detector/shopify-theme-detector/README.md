# Shopify Theme Detector

Detect the Shopify theme used by any Shopify store, directly from your WordPress site. No external API, no Node.js build step — works on shared hosting out of the box.

## Folder Structure

```
shopify-theme-detector/
├── shopify-theme-detector.php      # Main plugin file, autoloader, activation hooks
├── includes/
│   ├── Activator.php               # Activation/deactivation
│   ├── Plugin.php                  # Singleton bootstrapper
│   ├── Settings.php                # Options API wrapper
│   ├── RateLimiter.php             # Per-IP rate limiting (transients)
│   ├── Cache.php                   # Result caching (transients)
│   ├── Schema.php                  # Schema.org FAQPage JSON-LD
│   ├── Shortcode.php               # [shopify_theme_detector] shortcode
│   ├── Ajax/
│   │   └── AjaxHandler.php         # wp_ajax / wp_ajax_nopriv handler
│   └── Detector/
│       ├── UrlValidator.php        # URL validation + SSRF protection
│       ├── HttpFetcher.php         # Hardened wp_safe_remote_get wrapper
│       ├── FingerprintDatabase.php # Loads data/fingerprints.json
│       └── ThemeDetector.php       # 8-method detection engine + scoring
├── admin/
│   ├── AdminSettings.php           # Settings -> Shopify Theme Detector page
│   └── views/settings-page.php
├── public/
│   └── Frontend.php                # Conditional asset enqueueing
├── assets/
│   ├── css/style.css               # Responsive, dark-mode aware styles
│   └── js/script.js                # Vanilla JS AJAX logic (no build step)
├── templates/
│   ├── detector-form.php           # Shortcode markup
│   └── faq.php                     # Optional FAQ + JSON-LD
└── data/
    └── fingerprints.json           # Theme fingerprint database
```

## Installation

1. Zip the `shopify-theme-detector` folder (or download it as-is).
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Select the ZIP file and click **Install Now**, then **Activate**.
4. No further setup is required — sensible defaults are applied on activation.

## Usage

Add the shortcode to any Page, Post, or Gutenberg/Elementor text block:

```
[shopify_theme_detector]
```

Disable the bundled FAQ section if you don't want it:

```
[shopify_theme_detector faq="no"]
```

Visitors enter a store URL (e.g. `https://gymshark.com`), click **Detect Theme**, and see a results card with:

- Detected theme name
- Theme ID (when exposed by the store)
- Theme vendor / developer
- Theme type (free / paid)
- Theme version (when available)
- Published status
- Screenshot preview (when the store exposes an `og:image` tag)
- Confidence score
- Detection method used
- Detection timestamp

### Admin Settings

Go to **Settings → Shopify Theme Detector** to:

- Enable/disable result caching
- Set cache duration (hours)
- Set the per-IP rate limit (requests/hour)
- Toggle debug mode
- View the loaded theme fingerprint database
- Clear all cached results immediately

## How Detection Works

The engine makes a single outbound request to the store's homepage (the URL the visitor supplied) and runs eight independent heuristics against the returned HTML:

1. **Asset URL Fingerprint Match** – parses `cdn.shopify.com/.../t/<id>/assets/...` paths.
2. **Theme Script Inspection** – inspects `<script>` tags referencing theme assets.
3. **Theme Metadata Inspection** – parses the `Shopify.theme = {...}` JS object many storefronts expose (name, id, version, published role). This is the strongest, highest-confidence signal when present.
4. **Fingerprint String Search** – searches the raw HTML for known theme-identifying strings.
5. **CSS Asset Analysis** – inspects linked stylesheet filenames.
6. **JS Asset Analysis** – inspects all enqueued script filenames.
7. **Section & Template Pattern Analysis** – matches known theme-specific CSS class names.
8. **Storefront HTML Structure Analysis** – confirms Online Store 2.0 section-based markup.

Results are aggregated into a single confidence score (0–100%) and the highest-confidence theme match is returned. If the `Shopify.theme` metadata object is found, it is trusted directly (confidence up to 99%); otherwise the engine combines corroborating heuristic matches.

Bundled fingerprints include: Dawn, Sense, Refresh, Craft, Studio, Taste, Ride, Origin, Prestige, Impulse, Turbo, Flex, Motion, Warehouse, Empire, Expanse, Broadcast, Symmetry, Pipeline, Focal, and Impact.

## Security

- **SSRF protection** — `UrlValidator` only allows `http`/`https`, resolves the hostname, and rejects private/reserved/loopback IP ranges (RFC1918, link-local, etc.) before any request is made. Fetching also goes through `wp_safe_remote_get()`, which re-validates every redirect hop.
- **CSRF protection** — every AJAX request requires a valid WordPress nonce (`check_ajax_referer`).
- **XSS protection** — all dynamic output is escaped (`esc_html`, `esc_attr`, `esc_url`) server-side, and all dynamic content inserted client-side goes through an HTML-escaping helper before being written to the DOM.
- **Rate limiting** — capped per-IP via transients (default: 10 requests/hour, configurable), independent of caching.
- **Input sanitization** — all `$_POST`/`$_SERVER` values are sanitized before use; nothing is passed to `eval`, `include` with user input, or raw SQL.
- **No remote abuse vector** — the plugin never proxies arbitrary requests; it only fetches the single homepage URL needed to run detection, with a byte cap (1.5MB) and short timeout.

## Performance

- **No database tables** — only the WordPress Options API (one row) and Transients API (cache + rate-limit counters) are used.
- **Caching** — successful results are cached for a configurable duration (default 24h) so repeat lookups of the same store are instant and make zero outbound requests.
- **No external API** — detection runs entirely against the store's own public homepage; no third-party services, no API keys, no Node build step.
- **Conditional asset loading** — CSS/JS only load on pages where the shortcode is actually rendered.
- **Bounded fetch** — requests are capped at ~1.5MB and a 12-second timeout, keeping shared-hosting resource usage predictable.

## Limitations

Detection relies on publicly visible markup. Heavily customized themes, stores that strip the `Shopify.theme` metadata object, or themes outside the bundled fingerprint database may return a low-confidence or "Unknown" result. The fingerprint database can be extended by editing `data/fingerprints.json`.
