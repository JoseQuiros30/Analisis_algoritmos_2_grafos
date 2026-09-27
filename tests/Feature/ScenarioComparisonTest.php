<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Services\RouteCostService;
use App\Services\ScenarioComparisonService;
use Tests\TestCase;

class ScenarioComparisonTest extends TestCase
{
    public function test_compares_all_four_scenarios_with_a_cost_breakdown(): void
    {
        $connection = Connection::factory()->make([
            'origin_station_id' => 1, 'destination_station_id' => 2,
            'base_time' => 4, 'weather_penalty' => 1, 'peak_hour_penalty' => 2,
            'congestion_penalty' => 8, 'transfer_penalty' => 9,
        ]);
        $attributes = $connection->getAttributes();

        $comparison = (new ScenarioComparisonService(new RouteCostService))->compare($connection);

        $this->assertSame(['normal', 'rain', 'peak_hour', 'rain_peak_hour'], array_keys($comparison));
        $this->assertSame([4, 5, 6, 7], array_column(array_column($comparison, 'costs'), 'total'));
        $this->assertSame([0, 1, 2, 3], array_column($comparison, 'difference'));
        $this->assertSame([
            'base_time' => 4, 'weather_penalty' => 1, 'peak_hour_penalty' => 2,
            'congestion_penalty' => 0, 'transfer_penalty' => 0, 'total' => 7,
        ], $comparison['rain_peak_hour']['costs']);
        $this->assertSame($attributes, $connection->getAttributes());
    }

    public function test_zero_penalties_produce_equal_costs_without_a_false_difference(): void
    {
        $connection = Connection::factory()->make([
            'origin_station_id' => 1, 'destination_station_id' => 2,
            'base_time' => 3, 'weather_penalty' => 0, 'peak_hour_penalty' => 0,
        ]);

        $comparison = (new ScenarioComparisonService(new RouteCostService))->compare($connection);

        $this->assertSame([3, 3, 3, 3], array_column(array_column($comparison, 'costs'), 'total'));
        $this->assertSame([0, 0, 0, 0], array_column($comparison, 'difference'));
    }
}
