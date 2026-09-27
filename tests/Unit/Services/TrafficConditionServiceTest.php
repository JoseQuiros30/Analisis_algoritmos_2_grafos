<?php

namespace Tests\Unit\Services;

use App\Services\TrafficConditionService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrafficConditionServiceTest extends TestCase
{
    #[DataProvider('hours')]
    public function test_classifies_peak_boundaries_and_automatic_congestion(string $time, bool $peak): void
    {
        $result = (new TrafficConditionService)->evaluate($time);

        $this->assertSame([
            'is_peak_hour' => $peak,
            'congestion_level' => $peak ? 'high' : 'low',
            'is_congested' => $peak,
            'congestion_multiplier' => $peak ? 2 : 0,
        ], $result);
    }

    public static function hours(): array
    {
        return [
            'midnight' => ['00:00', false], 'before morning' => ['05:59', false],
            'morning start' => ['06:00', true], 'morning end included' => ['08:59', true],
            'morning end excluded' => ['09:00', false], 'before afternoon' => ['15:59', false],
            'afternoon start' => ['16:00', true], 'afternoon end included' => ['18:59', true],
            'afternoon end excluded' => ['19:00', false], 'end of day' => ['23:59', false],
        ];
    }

    #[DataProvider('levels')]
    public function test_explicit_congestion_does_not_override_peak_detection(string $time, string $level, bool $peak, int $multiplier): void
    {
        $result = (new TrafficConditionService)->evaluate($time, $level);

        $this->assertSame([
            'is_peak_hour' => $peak, 'congestion_level' => $level,
            'is_congested' => $multiplier > 0, 'congestion_multiplier' => $multiplier,
        ], $result);
    }

    public static function levels(): array
    {
        return [
            'low at peak' => ['07:00', 'low', true, 0],
            'medium at peak' => ['07:00', 'medium', true, 1],
            'high outside peak' => ['12:00', 'high', false, 2],
        ];
    }

    #[DataProvider('invalidHours')]
    public function test_rejects_invalid_times(string $time): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HH:MM');

        (new TrafficConditionService)->evaluate($time);
    }

    public static function invalidHours(): array
    {
        return [
            'empty' => [''], 'short hour' => ['6:00'], 'hour overflow' => ['24:00'],
            'minute overflow' => ['06:60'], 'seconds' => ['06:00:00'],
            'space' => [' 06:00'], 'newline' => ["06:00\n"], 'date' => ['2026-09-27 06:00'],
        ];
    }

    public function test_rejects_unknown_congestion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('low, medium o high');

        (new TrafficConditionService)->evaluate('07:00', 'extreme');
    }
}
