<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

final class MosaicoraOgJsonLdOptions
{
    /**
     * @param string|array<string, string>|null $context
     * @param string|array<string, mixed>|list<array<string, mixed>>|null $author
     * @param string|array<string, mixed>|list<array<string, mixed>>|null $publisher
     * @param string|array<string, mixed>|list<string|array<string, mixed>>|null $image
     * @param array<string, mixed>|null $offers
     * @param array<string, mixed>|null $additionalFields
     */
    public function __construct(
        public readonly string $schemaType,
        public readonly string|array|null $context = null,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $url = null,
        public readonly ?string $inLanguage = null,
        public readonly string|array|null $author = null,
        public readonly string|array|null $publisher = null,
        public readonly string|array|null $image = null,
        public readonly ?string $headline = null,
        public readonly ?array $offers = null,
        public readonly ?array $additionalFields = null,
        public readonly ?MosaicoraOgOverride $mosaicoraOg = null,
    ) {
    }
}
