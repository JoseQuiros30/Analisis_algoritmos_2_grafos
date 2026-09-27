<?php

namespace App\Services;

use InvalidArgumentException;

class TrafficConditionService
{
    public const CONGESTION_LOW = 'low';

    public const CONGESTION_MEDIUM = 'medium';

    public const CONGESTION_HIGH = 'high';

    /**
     * Academic simulation, not official Metro schedules or live traffic.
     * HH:MM is local time in America/Bogota, with no date/weekend distinction.
     * Peak intervals include their start and exclude their end: [06:00,09:00),
     * [16:00,19:00). Null congestion selects high at peak, low otherwise.
     * An explicit congestion level overrides congestion only, not peak hour.
     *
     * @return array{is_peak_hour: bool, congestion_level: string, is_congested: bool, congestion_multiplier: int}
     */
    public function evaluate(string $departureTime, ?string $congestionLevel = null): array
    {
        if (! preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/', $departureTime)) {
            throw new InvalidArgumentException('La hora debe tener formato HH:MM entre 00:00 y 23:59.');
        }

        $minutes = (int) substr($departureTime, 0, 2) * 60 + (int) substr($departureTime, 3, 2);
        $isPeakHour = ($minutes >= 360 && $minutes < 540) || ($minutes >= 960 && $minutes < 1140);
        $level = $congestionLevel ?? ($isPeakHour ? self::CONGESTION_HIGH : self::CONGESTION_LOW);
        $multiplier = match ($level) {
            self::CONGESTION_LOW => 0,
            self::CONGESTION_MEDIUM => 1,
            self::CONGESTION_HIGH => 2,
            default => throw new InvalidArgumentException('La congestion debe ser low, medium o high.'),
        };

        return [
            'is_peak_hour' => $isPeakHour,
            'congestion_level' => $level,
            'is_congested' => $multiplier > 0,
            'congestion_multiplier' => $multiplier,
        ];
    }
}
