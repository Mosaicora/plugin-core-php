# `mosaicora/plugin-core-php`

Framework-agnostic PHP helpers for Mosaicora Open Graph image URLs and
`mosaicora:og` JSON-LD v3 overrides.

## Install

```bash
composer require mosaicora/plugin-core-php
```

The package requires PHP 8.1 or newer and has no runtime dependencies.

## Build an Open Graph image URL

```php
use Mosaicora\PluginCore\OgImageUrl;
use Mosaicora\PluginCore\OgImageUrlOptions;

$imageUrl = OgImageUrl::build(new OgImageUrlOptions(
    siteId: '321cac22d2103fb1660c50bd',
    pageHref: 'https://example.com/products/view',
));
```

The output is deterministic: it normalizes duplicate and trailing path slashes,
keeps readable UTF-8 paths, ignores fragments, filters default tracking/control
parameters, and percent-encodes the sorted remaining page query into the CDN
path before `.jpg`. The CDN ignores query parameters after `.jpg` for source
page lookup.

The home page produces `https://cdn.mosaicora.io/s/{siteId}.jpg` nested paths
remain below `/s/{siteId}`. Pass `baseOrigin` to use a different CDN origin.

### Refreshing social image URLs

Social networks can retain a link preview after their first crawl. A new image
URL helps them retrieve a new asset when they re-scrape page metadata, but it
does not force them to re-scrape.

Use an explicit release or content revision when one is available:

```php
$imageUrl = OgImageUrl::build(new OgImageUrlOptions(
    siteId: '321cac22d2103fb1660c50bd',
    pageHref: 'https://example.com/products/view',
    cacheVersion: 'release-2026-07',
));
```

Or opt in to a UTC schedule with `cacheBuster`: `daily`, `weekly`, `monthly`,
or a positive duration such as `15m`, `6h`, or `7d`. `cacheVersion` takes
precedence over `cacheBuster` and replaces any existing `v` parameter. Keep the
default stable URL unless your content genuinely needs rotation. `monthly` is a
good production default for most pages. The reserved `v` token remains after
`.jpg` and is cache-only.

## Add a v3 JSON-LD override

Keep existing Schema.org data and add only values Mosaicora should use exactly:

```php
use Mosaicora\PluginCore\MosaicoraOgJsonLd;
use Mosaicora\PluginCore\MosaicoraOgJsonLdOptions;
use Mosaicora\PluginCore\MosaicoraOgOverride;

$jsonLd = MosaicoraOgJsonLd::build(new MosaicoraOgJsonLdOptions(
    schemaType: 'Product',
    name: 'Example product',
    offers: [
        '@type' => 'Offer',
        'price' => '49',
        'priceCurrency' => 'USD',
    ],
    mosaicoraOg: new MosaicoraOgOverride(
        templateId: '6a36446a0021410e8044',
        semanticValues: [
            'content.title' => 'Example product',
            'content.description' => 'A polished preview for every product page.',
            'product.price' => '$49',
            'product.features' => ['Fast setup', 'Consistent previews'],
        ],
    ),
));

$serialized = MosaicoraOgJsonLd::serialize($jsonLd);
```

`serialize()` removes null values recursively and escapes `<`, making the result
safe to embed in an HTML JSON-LD script element. The helper deliberately does
not reject semantic role names or values at runtime, applications should use
the documented v3 contract below.

## Public API

- `OgImageUrl::build(OgImageUrlOptions $options): string`
- `OgImageCacheBuster::build(string $schedule, ?DateTimeInterface $now = null): string`
- `MosaicoraOgJsonLd::build(MosaicoraOgJsonLdOptions $options): array`
- `MosaicoraOgJsonLd::serialize(array $value): string`
- Immutable value objects: `OgImageUrlOptions`, `MosaicoraOgJsonLdOptions`,
  `MosaicoraOgOverride`, and `MosaicoraOgMetric`.

See [Mosaicora OG Overrides v3](docs/mosaicora-og-overrides.md) for the
semantic-role contract.

## Development and releases

```bash
composer install
composer validate --strict
composer test
composer package:check
```

Pull requests and pushes run this validation on PHP 8.1 through 8.4. To release,
create and push a semantic `v{version}` Git tag. The release workflow validates
the package and creates a GitHub Release. Packagist resolves the installable
version from that Git tag; register the repository there and enable its GitHub
webhook so tags are synchronized automatically. Do not add a `version` field to
`composer.json` for this VCS-distributed package.

## Security

Please read [SECURITY.md](SECURITY.md) before reporting a vulnerability.

Licensed under the [Apache License 2.0](LICENSE).
