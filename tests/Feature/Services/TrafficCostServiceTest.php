<?php

namespace Tests\Feature\Services;

use App\Models\Connection;
use App\Services\DijkstraService;
use App\Services\TrafficCostService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrafficCostServiceTest extends TestCase
{
    #[DataProvider('scenarios')]
    public function test_integrates_time_weather_congestion_and_transfer_once(string $time, string $weather, ?string $level, bool $transfer, array $expected): void
    {
        $connection = $this->connection();
        $attributes = $connection->getAttributes();

        $result = $this->app->make(TrafficCostService::class)->calculate($connection, $time, $weather, $level, $transfer);

        $this->assertSame($expected, array_values($result['costs']));
        $this->assertSame($attributes, $connection->getAttributes());
    }

    public static function scenarios(): array
    {
        return [
            'normal automatic' => ['12:00', 'normal', null, false, [4, 0, 0, 0, 0, 4]],
            'peak automatic' => ['07:00', 'normal', null, false, [4, 0, 2, 2, 0, 8]],
            'rain and peak automatic' => ['17:00', 'rain', null, false, [4, 1, 2, 2, 0, 9]],
            'low override' => ['07:00', 'rain', 'low', false, [4, 1, 2, 0, 0, 7]],
            'medium override' => ['07:00', 'rain', 'medium', false, [4, 1, 2, 1, 0, 8]],
            'high off peak' => ['12:00', 'normal', 'high', false, [4, 0, 0, 2, 0, 6]],
            'explicit transfer' => ['17:00', 'rain', 'high', true, [4, 1, 2, 2, 3, 12]],
        ];
    }

    public function test_repeated_calls_do_not_accumulate_penalties(): void
    {
        $service = $this->app->make(TrafficCostService::class);
        $connection = $this->connection();

        $first = $service->calculate($connection, '07:00');
        $normal = $service->calculate($connection, '12:00');
        $again = $service->calculate($connection, '07:00');

        $this->assertSame($first, $again);
        $this->assertSame(4, $normal['costs']['total']);
    }

    public function test_dynamic_weights_change_the_route_on_a_simulated_graph(): void
    {
        $service = $this->app->make(TrafficCostService::class);
        $direct = $this->connection();
        $detour = $this->connection();
        $detour->fill(['base_time' => 3, 'peak_hour_penalty' => 0, 'congestion_penalty' => 0]);

        $normalDirect = $service->calculate($direct, '12:00')['costs']['total'];
        $peakDirect = $service->calculate($direct, '07:00')['costs']['total'];
        $detourWeight = $service->calculate($detour, '07:00')['costs']['total'];
        $graph = ['A' => ['D' => $normalDirect, 'B' => $detourWeight], 'B' => ['D' => $detourWeight], 'D' => []];
        $normal = (new DijkstraService)->findShortestPath($graph, 'A', 'D');
        $graph['A']['D'] = $peakDirect;
        $peak = (new DijkstraService)->findShortestPath($graph, 'A', 'D');

        $this->assertSame(['A', 'D'], $normal['path']);
        $this->assertSame(4.0, $normal['totalCost']);
        $this->assertSame(['A', 'B', 'D'], $peak['path']);
        $this->assertSame(6.0, $peak['totalCost']);
    }

    public function test_zero_congestion_penalty_stays_zero_at_high_level(): void
    {
        $connection = $this->connection();
        $connection->congestion_penalty = 0;

        $result = $this->app->make(TrafficCostService::class)->calculate($connection, '07:00');

        $this->assertSame(0, $result['costs']['congestion_penalty']);
        $this->assertSame(6, $result['costs']['total']);
    }

    public function test_rejects_congestion_multiplication_overflow(): void
    {
        $connection = $this->connection();
        $connection->congestion_penalty = PHP_INT_MAX;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('rango');

        $this->app->make(TrafficCostService::class)->calculate($connection, '07:00');
    }

    private function connection(): Connection
    {
        return Connection::factory()->make([
            'origin_station_id' => 1, 'destination_station_id' => 2,
            'base_time' => 4, 'weather_penalty' => 1, 'peak_hour_penalty' => 2,
            'congestion_penalty' => 1, 'transfer_penalty' => 3,
        ]);
    }
}
