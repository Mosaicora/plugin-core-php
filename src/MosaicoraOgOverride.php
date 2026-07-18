<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

/**
 * @phpstan-type SemanticMetric array{id: string, label: string, value: string}
 * @phpstan-type SemanticValues array<string, string|bool|list<string>|list<SemanticMetric>>
 */
final class MosaicoraOgOverride
{
    /** @param array<string, mixed> $semanticValues */
    public function __construct(
        public readonly array $semanticValues,
        public readonly int $schemaVersion = 3,
        public readonly ?string $templateId = null,
    ) {
    }
}
