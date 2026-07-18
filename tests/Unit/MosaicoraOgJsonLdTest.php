<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore\Tests\Unit;

use Mosaicora\PluginCore\MosaicoraOgJsonLd;
use Mosaicora\PluginCore\MosaicoraOgJsonLdOptions;
use Mosaicora\PluginCore\MosaicoraOgOverride;
use PHPUnit\Framework\TestCase;

final class MosaicoraOgJsonLdTest extends TestCase
{
    public function testBuildsEveryV3SemanticValueCategory(): void
    {
        $jsonLd = MosaicoraOgJsonLd::build(new MosaicoraOgJsonLdOptions(
            schemaType: 'Product',
            name: 'Mosaicora OG Generator',
            additionalFields: ['optional' => null],
            mosaicoraOg: new MosaicoraOgOverride(
                templateId: '6a36446a0021410e8044',
                semanticValues: [
                    'content.title' => 'Mosaicora OG Generator',
                    'product.features' => ['Fast', 'Consistent'],
                    'social.verified' => true,
                    'analytics.metrics' => [
                        ['id' => 'conversion', 'label' => 'Conversion', 'value' => '24%'],
                    ],
                ],
            ),
        ));

        self::assertSame([
            'schemaVersion' => 3,
            'templateId' => '6a36446a0021410e8044',
            'semanticValues' => [
                'content.title' => 'Mosaicora OG Generator',
                'product.features' => ['Fast', 'Consistent'],
                'social.verified' => true,
                'analytics.metrics' => [
                    ['id' => 'conversion', 'label' => 'Conversion', 'value' => '24%'],
                ],
            ],
        ], $jsonLd['mosaicora:og']);
        self::assertArrayNotHasKey('optional', $jsonLd);
    }

    public function testPreservesEmptyRequiredSemanticValuesAndTemplateOnlyOverrides(): void
    {
        $jsonLd = MosaicoraOgJsonLd::build(new MosaicoraOgJsonLdOptions(
            schemaType: 'WebPage',
            mosaicoraOg: new MosaicoraOgOverride([], templateId: '6a36446a0021410e8044'),
        ));

        self::assertSame([
            'schemaVersion' => 3,
            'templateId' => '6a36446a0021410e8044',
            'semanticValues' => [],
        ], $jsonLd['mosaicora:og']);
    }

    public function testRemovesNullValuesRecursively(): void
    {
        $jsonLd = MosaicoraOgJsonLd::build(new MosaicoraOgJsonLdOptions(
            schemaType: 'Article',
            author: ['name' => 'Ava', 'image' => null],
            additionalFields: ['keywords' => ['Mosaicora', null, 'Open Graph']],
        ));

        self::assertSame(['name' => 'Ava'], $jsonLd['author']);
        self::assertSame(['Mosaicora', 'Open Graph'], $jsonLd['keywords']);
    }

    public function testSerializesJsonLdWithoutScriptBreakingCharacters(): void
    {
        $serialized = MosaicoraOgJsonLd::serialize(['title' => '</script><script>alert(1)</script>']);

        self::assertStringNotContainsString('<', $serialized);
        self::assertStringContainsString('\\u003c/script>', $serialized);
    }
}
