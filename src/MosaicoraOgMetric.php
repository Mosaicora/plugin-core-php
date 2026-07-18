<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

final class MosaicoraOgMetric
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $value,
    ) {
    }

    /** @return array{id: string, label: string, value: string} */
    public function toArray(): array
    {
        return ['id' => $this->id, 'label' => $this->label, 'value' => $this->value];
    }
}
