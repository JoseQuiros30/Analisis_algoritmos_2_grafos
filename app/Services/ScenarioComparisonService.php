<?php

namespace App\Services;

use App\Models\Connection;

class ScenarioComparisonService
{
    public function __construct(private RouteCostService $routeCostService) {}

    /** @return array<string, array{label: string, weather: string, is_peak_hour: bool}> */
    public static function presets(): array
    {
        return [
            'normal' => ['label' => 'Normal', 'weather' => RouteCostService::WEATHER_NORMAL, 'is_peak_hour' => false],
            'rain' => ['label' => 'Lluvia', 'weather' => RouteCostService::WEATHER_RAIN, 'is_peak_hour' => false],
            'peak_hour' => ['label' => 'Hora pico', 'weather' => RouteCostService::WEATHER_NORMAL, 'is_peak_hour' => true],
            'rain_peak_hour' => ['label' => 'Lluvia + hora pico', 'weather' => RouteCostService::WEATHER_RAIN, 'is_peak_hour' => true],
        ];
    }

    /**
     * Compare one directed edge, with congestion and transfer disabled.
     *
     * @return array<string, array{label: string, weather: string, is_peak_hour: bool, costs: array{base_time: int, weather_penalty: int, peak_hour_penalty: int, congestion_penalty: int, transfer_penalty: int, total: int}, difference: int}>
     */
    public function compare(Connection $connection): array
    {
        $results = [];
        $normalCost = $this->routeCostService->calculate($connection)['total'];

        foreach (self::presets() as $key => $preset) {
            $costs = $this->routeCostService->calculate(
                $connection,
                weather: $preset['weather'],
                isPeakHour: $preset['is_peak_hour'],
            );

            $results[$key] = [
                ...$preset,
                'costs' => $costs,
                'difference' => $costs['total'] - $normalCost,
            ];
        }

        return $results;
    }
}
