<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

/**
 * Inputs for a deterministic Mosaicora Open Graph image URL.
 */
final class OgImageUrlOptions
{
    public function __construct(
        public readonly string $siteId,
        public readonly string $pageHref,
        public readonly ?string $baseOrigin = null,
        public readonly ?string $cacheBuster = null,
        public readonly ?string $cacheVersion = null,
    ) {
    }
}
