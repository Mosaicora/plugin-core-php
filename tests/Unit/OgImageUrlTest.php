<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use Mosaicora\PluginCore\OgImageCacheBuster;
use Mosaicora\PluginCore\OgImageUrl;
use Mosaicora\PluginCore\OgImageUrlOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RangeException;

final class OgImageUrlTest extends TestCase
{
    #[DataProvider('urlCases')]
    public function testBuildsDeterministicImageUrls(OgImageUrlOptions $options, string $expected): void
    {
        self::assertSame($expected, OgImageUrl::build($options));
    }

    /** @return iterable<string, array{OgImageUrlOptions, string}> */
    public static function urlCases(): iterable
    {
        yield 'homepage query' => [
            new OgImageUrlOptions('site-123', 'https://example.com/?sku=123&page=2'),
            'https://cdn.mosaicora.io/s/site-123/%3Fpage%3D2%26sku%3D123.jpg',
        ];
        yield 'nested URL sorts every query value' => [
            new OgImageUrlOptions('site-123', 'https://example.com/products/view/?sku=123&page=2&campaign=launch&a=2&a=1'),
            'https://cdn.mosaicora.io/s/site-123/products/view%3Fa%3D1%26a%3D2%26campaign%3Dlaunch%26page%3D2%26sku%3D123.jpg',
        ];
        yield 'normalizes path and ignores fragment' => [
            new OgImageUrlOptions('site-123', 'https://example.com//products//view///?sku=123#details'),
            'https://cdn.mosaicora.io/s/site-123/products/view%3Fsku%3D123.jpg',
        ];
        yield 'keeps unicode paths readable' => [
            new OgImageUrlOptions('site-123', 'https://example.com/%E4%B8%AD%E8%8F%AF%E4%BA%BA%E6%B0%91%E5%85%B1%E5%92%8C%E5%9B%BD?lang=zh'),
            'https://cdn.mosaicora.io/s/site-123/中華人民共和国%3Flang%3Dzh.jpg',
        ];
        yield 'uses valid canonical URL' => [
            new OgImageUrlOptions('site-123', 'https://example.com/products/view/?sku=123'),
            'https://cdn.mosaicora.io/s/site-123/products/view%3Fsku%3D123.jpg',
        ];
        yield 'allows a custom CDN origin' => [
            new OgImageUrlOptions('site-123', 'https://example.com/products', baseOrigin: 'https://cdn.example.test/'),
            'https://cdn.example.test/s/site-123/products.jpg',
        ];
    }

    public function testCacheVersionReplacesExistingVersionAndIsSafelyEncoded(): void
    {
        self::assertSame(
            'https://cdn.mosaicora.io/s/site-123/products/view%3Fsku%3D123.jpg?v=release+42%2Fblue',
            OgImageUrl::build(new OgImageUrlOptions(
                'site-123',
                'https://example.com/products/view?sku=123&v=legacy',
                cacheBuster: 'daily',
                cacheVersion: 'release 42/blue',
            )),
        );
    }

    public function testScheduledCacheBusterReplacesExistingVersion(): void
    {
        $url = OgImageUrl::build(new OgImageUrlOptions(
            'site-123',
            'https://example.com/products/view?sku=123&v=legacy&page=2',
            cacheBuster: 'daily',
        ));

        self::assertMatchesRegularExpression(
            '#^https://cdn\\.mosaicora\\.io/s/site-123/products/view%3Fpage%3D2%26sku%3D123\\.jpg\\?v=\\d{4}-\\d{2}-\\d{2}$#',
            $url,
        );
    }

    public function testPreservesExistingVersionWhenCacheBustingIsDisabled(): void
    {
        self::assertSame(
            'https://cdn.mosaicora.io/s/site-123/products/view%3Fsku%3D123.jpg?v=legacy',
            OgImageUrl::build(new OgImageUrlOptions('site-123', 'https://example.com/products/view?v=legacy&sku=123')),
        );
    }

    public function testRejectsEmptyCacheVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cacheVersion must not be empty');

        OgImageUrl::build(new OgImageUrlOptions('site-123', 'https://example.com/products', cacheVersion: ''));
    }

    public function testRejectsInvalidPageHref(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pageHref must be a valid absolute HTTP URL');

        OgImageUrl::build(new OgImageUrlOptions('site-123', 'not a valid URL'));
    }

    public function testRemovesDefaultTrackingAndControlParametersBeforeEncoding(): void
    {
        self::assertSame(
            'https://cdn.mosaicora.io/s/site-123/products/view%3Fcampaign%3Dspring.jpg',
            OgImageUrl::build(new OgImageUrlOptions(
                'site-123',
                'https://example.com/products/view?utm_source=newsletter&fbclid=abc&campaign=spring&force=true',
            )),
        );
    }

    #[DataProvider('cacheBusterCases')]
    public function testBuildsUtcCacheBusters(string $schedule, string $date, string $expected): void
    {
        self::assertSame($expected, OgImageCacheBuster::build($schedule, new DateTimeImmutable($date)));
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function cacheBusterCases(): iterable
    {
        yield 'daily' => ['daily', '2026-12-31T23:59:59.999Z', '2026-12-31'];
        yield 'monthly' => ['monthly', '2027-01-01T00:00:00.000Z', '2027-01'];
        yield 'ISO week before boundary' => ['weekly', '2021-01-01T12:00:00.000Z', '2020-W53'];
        yield 'ISO week at boundary' => ['weekly', '2021-01-04T00:00:00.000Z', '2021-W01'];
        yield '15 minute duration' => ['15m', '2026-07-17T12:34:56.000Z', '1982546'];
        yield '6 hour duration' => ['6h', '2026-07-17T12:34:56.000Z', '82606'];
        yield '7 day duration' => ['7d', '2026-07-17T12:34:56.000Z', '2950'];
    }

    #[DataProvider('invalidSchedules')]
    public function testRejectsInvalidCacheBusterSchedules(string $schedule): void
    {
        $this->expectException(InvalidArgumentException::class);
        OgImageCacheBuster::build($schedule, new DateTimeImmutable('2026-07-17T12:34:56Z'));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSchedules(): iterable
    {
        yield 'zero' => ['0h'];
        yield 'negative' => ['-1h'];
        yield 'fraction' => ['1.5h'];
        yield 'word' => ['hourly'];
        yield 'week unit' => ['1w'];
    }

    public function testRejectsOversizedCacheBusterDuration(): void
    {
        $this->expectException(RangeException::class);
        OgImageCacheBuster::build('999999999999999999999h', new DateTimeImmutable('2026-07-17T12:34:56Z'));
    }
}
