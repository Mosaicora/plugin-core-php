<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

use JsonException;

final class MosaicoraOgJsonLd
{
    public const SCHEMA_ORG_CONTEXT = 'https://schema.org';
    public const MOSAICORA_OG_NAMESPACE = 'https://mosaicora.io/ns/og#';

    /** @return array<string, mixed> */
    public static function build(MosaicoraOgJsonLdOptions $options): array
    {
        $result = [
            '@context' => $options->context ?? [
                'schema' => self::SCHEMA_ORG_CONTEXT,
                'mosaicora' => self::MOSAICORA_OG_NAMESPACE,
            ],
            '@type' => $options->schemaType,
        ];

        foreach ([
            'name' => $options->name,
            'headline' => $options->headline,
            'description' => $options->description,
            'url' => $options->url,
            'inLanguage' => $options->inLanguage,
            'author' => $options->author,
            'publisher' => $options->publisher,
            'image' => $options->image,
            'offers' => $options->offers,
        ] as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        foreach ($options->additionalFields ?? [] as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        if ($options->mosaicoraOg !== null) {
            $override = [
                'schemaVersion' => $options->mosaicoraOg->schemaVersion,
            ];
            if ($options->mosaicoraOg->templateId !== null) {
                $override['templateId'] = $options->mosaicoraOg->templateId;
            }
            $override['semanticValues'] = $options->mosaicoraOg->semanticValues;
            $result['mosaicora:og'] = $override;
        }

        return self::removeNullDeep($result);
    }

    /** @throws JsonException */
    public static function serialize(array $value): string
    {
        return str_replace(
            '<',
            '\\u003c',
            json_encode(
                self::removeNullDeep($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        );
    }

    /** @param array<string|int, mixed> $value @return array<string|int, mixed> */
    private static function removeNullDeep(array $value): array
    {
        $result = [];
        foreach ($value as $key => $entry) {
            if ($entry === null) {
                continue;
            }

            $result[$key] = is_array($entry) ? self::removeNullDeep($entry) : $entry;
        }

        return array_is_list($value) ? array_values($result) : $result;
    }
}
