<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore\Tests\Unit;

use Mosaicora\PluginCore\MosaicoraOgSemanticRoles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MosaicoraOgSemanticRolesTest extends TestCase
{
    public function testCatalogIncludesEveryV3ValueCategory(): void
    {
        self::assertSame('text', MosaicoraOgSemanticRoles::typeFor('content.title'));
        self::assertSame('list', MosaicoraOgSemanticRoles::typeFor('product.features'));
        self::assertSame('boolean', MosaicoraOgSemanticRoles::typeFor('social.verified'));
        self::assertSame('metrics', MosaicoraOgSemanticRoles::typeFor('analytics.metrics'));
        self::assertCount(45, MosaicoraOgSemanticRoles::definitions());
    }

    #[DataProvider('acceptedValues')]
    public function testAcceptsValuesMatchingTheRoleContract(string $role, mixed $value): void
    {
        self::assertTrue(MosaicoraOgSemanticRoles::acceptsValue($role, $value));
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function acceptedValues(): iterable
    {
        yield 'text' => ['content.title', 'A precise title'];
        yield 'empty list' => ['product.features', []];
        yield 'string list' => ['image.secondary', ['https://example.com/one.jpg']];
        yield 'boolean' => ['social.verified', false];
        yield 'metrics' => ['analytics.metrics', [
            ['id' => 'sales', 'label' => 'Sales', 'value' => '120'],
        ]];
    }

    #[DataProvider('rejectedValues')]
    public function testRejectsUnknownRolesAndInvalidValueShapes(string $role, mixed $value): void
    {
        self::assertFalse(MosaicoraOgSemanticRoles::acceptsValue($role, $value));
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function rejectedValues(): iterable
    {
        yield 'unknown role' => ['unknown.value', 'text'];
        yield 'wrong text type' => ['content.title', ['Title']];
        yield 'mixed list' => ['product.features', ['Fast', 2]];
        yield 'wrong boolean type' => ['social.verified', 'true'];
        yield 'incomplete metric' => ['analytics.metrics', [
            ['id' => 'sales', 'label' => 'Sales'],
        ]];
        yield 'metric with extra data' => ['analytics.metrics', [
            ['id' => 'sales', 'label' => 'Sales', 'value' => '120', 'secret' => 'no'],
        ]];
    }
}
