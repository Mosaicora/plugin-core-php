<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use RangeException;

final class OgImageCacheBuster
{
    private const CUSTOM_SCHEDULE_PATTERN = '/^([1-9][0-9]*)([mhd])$/D';

    private const MINUTE_MILLISECONDS = 60_000;
    private const HOUR_MILLISECONDS = 3_600_000;
    private const DAY_MILLISECONDS = 86_400_000;

    public static function build(string $schedule, ?DateTimeInterface $now = null): string
    {
        $date = self::utc($now ?? new DateTimeImmutable('now'));

        return match ($schedule) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->format('o-\\WW'),
            'monthly' => $date->format('Y-m'),
            default => self::buildDurationBucket($schedule, $date),
        };
    }

    private static function buildDurationBucket(string $schedule, DateTimeImmutable $date): string
    {
        if (preg_match(self::CUSTOM_SCHEDULE_PATTERN, $schedule, $matches) !== 1) {
            throw new InvalidArgumentException(
                'Invalid cache-buster schedule. Use "daily", "weekly", "monthly", or a positive whole-number duration such as "6h".',
            );
        }

        $amount = self::parsePositiveInteger($matches[1]);
        $unitMilliseconds = match ($matches[2]) {
            'm' => self::MINUTE_MILLISECONDS,
            'h' => self::HOUR_MILLISECONDS,
            'd' => self::DAY_MILLISECONDS,
        };

        if ($amount > intdiv(PHP_INT_MAX, $unitMilliseconds)) {
            throw new RangeException('The cache-buster duration is too large.');
        }

        $intervalMilliseconds = $amount * $unitMilliseconds;
        $milliseconds = ($date->getTimestamp() * 1000) + intdiv((int) $date->format('u'), 1000);

        return (string) floor($milliseconds / $intervalMilliseconds);
    }

    private static function parsePositiveInteger(string $value): int
    {
        $maximum = (string) PHP_INT_MAX;
        if (strlen($value) > strlen($maximum) || (strlen($value) === strlen($maximum) && strcmp($value, $maximum) > 0)) {
            throw new RangeException('The cache-buster duration is too large.');
        }

        return (int) $value;
    }

    private static function utc(DateTimeInterface $date): DateTimeImmutable
    {
        return (new DateTimeImmutable('@' . $date->format('U.u')))
            ->setTimezone(new DateTimeZone('UTC'));
    }
}
