<?php

namespace App\Services;

use App\Models\Connection;

class TrafficCostService
{
    public function __construct(
        private TrafficConditionService $trafficConditions,
        private RouteCostService $routeCosts,
    ) {}

    /**
     * Resolve simulated conditions and delegate all cost arithmetic to Jose's service.
     * Use the selected departure time for every edge to keep Dijkstra weights static
     * during a run. Transfer detection remains the caller's responsibility.
     *
     * @return array{
     *     conditions: array{is_peak_hour: bool, congestion_level: string, is_congested: bool, congestion_multiplier: int},
     *     costs: array{base_time: int, weather_penalty: int, peak_hour_penalty: int, congestion_penalty: int, transfer_penalty: int, total: int}
     * }
     */
    public function calculate(
        Connection $connection,
        string $departureTime,
        string $weather = RouteCostService::WEATHER_NORMAL,
        ?string $congestionLevel = null,
        bool $isTransfer = false,
    ): array {
        $conditions = $this->trafficConditions->evaluate($departureTime, $congestionLevel);
        $costs = $this->routeCosts->calculate(
            connection: $connection,
            weather: $weather,
            isPeakHour: $conditions['is_peak_hour'],
            isCongested: $conditions['is_congested'],
            isTransfer: $isTransfer,
            congestionMultiplier: $conditions['congestion_multiplier'],
        );

        return ['conditions' => $conditions, 'costs' => $costs];
    }
}
