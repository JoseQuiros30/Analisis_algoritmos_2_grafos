<?php

namespace App\Services;

use App\Models\Connection;
use InvalidArgumentException;

class RouteCostService
{
    public const WEATHER_NORMAL = 'normal';

    public const WEATHER_RAIN = 'rain';

    /**
     * Return simulated costs in whole minutes without changing the connection.
     * Traffic and transfer conditions must be determined by the caller.
     * Congestion multiplier: 0 (low), 1 (medium), 2 (high). The default
     * preserves the original boolean contract: congestion adds one penalty.
     *
     * @return array{base_time: int, weather_penalty: int, peak_hour_penalty: int, congestion_penalty: int, transfer_penalty: int, total: int}
     */
    public function calculate(
        Connection $connection,
        string $weather = self::WEATHER_NORMAL,
        bool $isPeakHour = false,
        bool $isCongested = false,
        bool $isTransfer = false,
        int $congestionMultiplier = 1,
    ): array {
        if (! in_array($weather, [self::WEATHER_NORMAL, self::WEATHER_RAIN], true)) {
            throw new InvalidArgumentException('El clima debe ser normal o rain.');
        }

        if (! in_array($congestionMultiplier, [0, 1, 2], true)) {
            throw new InvalidArgumentException('El multiplicador de congestion debe ser 0, 1 o 2.');
        }

        $costs = [];

        foreach (['base_time', 'weather_penalty', 'peak_hour_penalty', 'congestion_penalty', 'transfer_penalty'] as $field) {
            $rawValue = $connection->getAttributes()[$field] ?? null;
            $value = is_int($rawValue) || is_string($rawValue)
                ? filter_var($rawValue, FILTER_VALIDATE_INT)
                : false;
            $minimum = $field === 'base_time' ? 1 : 0;

            if ($value === false || $value < $minimum) {
                throw new InvalidArgumentException("El campo {$field} debe ser un entero mayor o igual a {$minimum}.");
            }

            $costs[$field] = $value;
        }

        $costs['weather_penalty'] = $weather === self::WEATHER_RAIN ? $costs['weather_penalty'] : 0;
        $costs['peak_hour_penalty'] = $isPeakHour ? $costs['peak_hour_penalty'] : 0;
        $costs['congestion_penalty'] = $isCongested ? $costs['congestion_penalty'] * $congestionMultiplier : 0;
        $costs['transfer_penalty'] = $isTransfer ? $costs['transfer_penalty'] : 0;
        $total = array_sum($costs);

        if (! is_int($total)) {
            throw new InvalidArgumentException('El costo total supera el rango de minutos enteros admitido.');
        }

        return [...$costs, 'total' => $total];
    }
}
