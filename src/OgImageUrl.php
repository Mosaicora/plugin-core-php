<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

use InvalidArgumentException;

final class OgImageUrl
{
    public const DEFAULT_BASE_ORIGIN = 'https://cdn.mosaicora.io';

    /** @var list<string> */
    private const DEFAULT_STRIPPED_PAGE_QUERY_KEYS = [
        'fbclid',
        'gclid',
        'wbraid',
        'gbraid',
        'mc_cid',
        'mc_eid',
        'ref',
        'ref_src',
        'regenerate',
        'force',
    ];

    public static function build(OgImageUrlOptions $options): string
    {
        $source = self::parseAbsoluteUrl($options->pageHref);
        $path = self::normalizePath($source['path'] ?? '/');
        $queryEntries = self::parseQuery($source['query'] ?? '');
        $cacheVersion = self::firstVersion($queryEntries);

        if ($options->cacheVersion !== null) {
            if ($options->cacheVersion === '') {
                throw new InvalidArgumentException('The cacheVersion must not be empty.');
            }

            $queryEntries = self::withoutVersion($queryEntries);
            $cacheVersion = $options->cacheVersion;
        } elseif ($options->cacheBuster !== null && $options->cacheBuster !== '') {
            $queryEntries = self::withoutVersion($queryEntries);
            $cacheVersion = OgImageCacheBuster::build($options->cacheBuster);
        }

        $pageQueryPathSuffix = self::buildPageQueryPathSuffix($queryEntries);
        $cacheVersionPart = $cacheVersion === null ? '' : '?' . self::buildSortedQuery([['v', $cacheVersion]]);
        $baseOrigin = rtrim($options->baseOrigin ?? self::DEFAULT_BASE_ORIGIN, '/');
        $siteId = self::encodeComponent($options->siteId);
        $imagePath = $path === '/'
            ? "/s/{$siteId}" . ($pageQueryPathSuffix === '' ? '' : "/{$pageQueryPathSuffix}") . '.jpg'
            : "/s/{$siteId}{$path}{$pageQueryPathSuffix}.jpg";

        return $baseOrigin . $imagePath . $cacheVersionPart;
    }

    /** @return array{path?: string, query?: string} */
    private static function parseAbsoluteUrl(string $href): array
    {
        $parsed = parse_url($href);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            throw new InvalidArgumentException('The pageHref must be a valid absolute HTTP URL.');
        }

        return $parsed;
    }

    private static function normalizePath(string $path): string
    {
        $normalized = preg_replace('#/{2,}#', '/', $path) ?? '/';
        if ($normalized !== '/' && str_ends_with($normalized, '/')) {
            $normalized = rtrim($normalized, '/');
        }

        return self::decodeUriPath($normalized === '' ? '/' : $normalized);
    }

    private static function decodeUriPath(string $path): string
    {
        $protected = preg_replace_callback(
            '/%(3B|2F|3F|3A|40|26|3D|2B|24|2C|23)/i',
            static fn (array $match): string => "\x1E" . strtoupper(substr($match[0], 1)) . "\x1F",
            $path,
        ) ?? $path;

        return preg_replace_callback(
            '/\x1E([0-9A-F]{2})\x1F/',
            static fn (array $match): string => '%' . $match[1],
            rawurldecode($protected),
        ) ?? $path;
    }

    /** @return list<array{0: string, 1: string}> */
    private static function parseQuery(string $query): array
    {
        if ($query === '') {
            return [];
        }

        $entries = [];
        foreach (explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $entries[] = [urldecode($key), urldecode($value)];
        }

        return $entries;
    }

    /** @param list<array{0: string, 1: string}> $entries */
    private static function buildSortedQuery(array $entries): string
    {
        usort($entries, static function (array $left, array $right): int {
            $keyComparison = strcmp($left[0], $right[0]);

            return $keyComparison !== 0 ? $keyComparison : strcmp($left[1], $right[1]);
        });

        return implode('&', array_map(
            static fn (array $entry): string => self::encodeFormComponent($entry[0]) . '=' . self::encodeFormComponent($entry[1]),
            $entries,
        ));
    }

    /** @param list<array{0: string, 1: string}> $entries */
    private static function buildPageQueryPathSuffix(array $entries): string
    {
        $pageEntries = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => $entry[0] !== 'v' && !self::isStrippedPageQueryKey($entry[0]),
        ));
        $query = self::buildSortedQuery($pageEntries);

        return $query === '' ? '' : rawurlencode("?{$query}");
    }

    private static function isStrippedPageQueryKey(string $key): bool
    {
        $normalizedKey = strtolower($key);

        return str_starts_with($normalizedKey, 'utm_') || in_array($normalizedKey, self::DEFAULT_STRIPPED_PAGE_QUERY_KEYS, true);
    }

    /** @param list<array{0: string, 1: string}> $entries */
    private static function firstVersion(array $entries): ?string
    {
        foreach ($entries as [$key, $value]) {
            if ($key === 'v') {
                return $value;
            }
        }

        return null;
    }

    /** @param list<array{0: string, 1: string}> $entries @return list<array{0: string, 1: string}> */
    private static function withoutVersion(array $entries): array
    {
        return array_values(array_filter($entries, static fn (array $entry): bool => $entry[0] !== 'v'));
    }

    private static function encodeFormComponent(string $value): string
    {
        return str_replace('%20', '+', rawurlencode($value));
    }

    private static function encodeComponent(string $value): string
    {
        return strtr(rawurlencode($value), ['%21' => '!', '%27' => "'", '%28' => '(', '%29' => ')', '%2A' => '*']);
    }
}
