<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Services\RouteCostService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteCostServiceTest extends TestCase
{
    #[DataProvider('conditions')]
    public function test_applies_only_the_penalties_enabled_for_each_scenario(
        string $weather,
        bool $peak,
        bool $congested,
        bool $transfer,
        array $expected,
    ): void {
        $connection = $this->connection();

        $result = (new RouteCostService)->calculate($connection, $weather, $peak, $congested, $transfer);

        $this->assertSame($expected, array_values($result));
    }

    public static function conditions(): array
    {
        return [
            'normal' => ['normal', false, false, false, [4, 0, 0, 0, 0, 4]],
            'rain' => ['rain', false, false, false, [4, 1, 0, 0, 0, 5]],
            'peak hour' => ['normal', true, false, false, [4, 0, 2, 0, 0, 6]],
            'rain and peak hour' => ['rain', true, false, false, [4, 1, 2, 0, 0, 7]],
            'congestion' => ['normal', false, true, false, [4, 0, 0, 1, 0, 5]],
            'transfer' => ['normal', false, false, true, [4, 0, 0, 0, 3, 7]],
            'academic example' => ['rain', true, true, false, [4, 1, 2, 1, 0, 8]],
            'all conditions' => ['rain', true, true, true, [4, 1, 2, 1, 3, 11]],
        ];
    }

    public function test_defaults_to_normal_and_exposes_the_complete_breakdown(): void
    {
        $result = (new RouteCostService)->calculate($this->connection());

        $this->assertSame([
            'base_time' => 4,
            'weather_penalty' => 0,
            'peak_hour_penalty' => 0,
            'congestion_penalty' => 0,
            'transfer_penalty' => 0,
            'total' => 4,
        ], $result);
    }

    public function test_calculations_do_not_accumulate_penalties_or_mutate_the_connection(): void
    {
        $connection = $this->connection();
        $attributes = $connection->getAttributes();
        $service = new RouteCostService;

        $rainy = $service->calculate($connection, 'rain', true, true, true);
        $normal = $service->calculate($connection);
        $rainyAgain = $service->calculate($connection, 'rain', true, true, true);

        $this->assertSame($attributes, $connection->getAttributes());
        $this->assertSame(4, $normal['total']);
        $this->assertSame($rainy, $rainyAgain);
    }

    public function test_uses_each_connections_own_costs_including_zero_penalties(): void
    {
        $connection = $this->connection();
        $connection->fill(['base_time' => 9, 'weather_penalty' => 0, 'peak_hour_penalty' => 4, 'congestion_penalty' => 0, 'transfer_penalty' => 2]);

        $result = (new RouteCostService)->calculate($connection, 'rain', true, true, true);

        $this->assertSame([9, 0, 4, 0, 2, 15], array_values($result));
    }

    public function test_rejects_unsupported_weather(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RouteCostService)->calculate($this->connection(), 'storm');
    }

    #[DataProvider('invalidCosts')]
    public function test_rejects_invalid_raw_costs_even_when_a_penalty_is_inactive(string $field, mixed $value): void
    {
        $connection = $this->connection();
        $connection->setAttribute($field, $value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($field);

        (new RouteCostService)->calculate($connection);
    }

    public static function invalidCosts(): array
    {
        return [
            'missing base' => ['base_time', null],
            'zero base' => ['base_time', 0],
            'negative base' => ['base_time', -1],
            'fractional base' => ['base_time', 2.5],
            'nonnumeric base' => ['base_time', 'invalid'],
            'boolean base' => ['base_time', true],
            'negative rain' => ['weather_penalty', -1],
            'negative peak' => ['peak_hour_penalty', -1],
            'negative congestion' => ['congestion_penalty', -1],
            'negative transfer' => ['transfer_penalty', -1],
            'missing penalty' => ['weather_penalty', null],
        ];
    }

    public function test_rejects_an_overflowing_total(): void
    {
        $connection = $this->connection();
        $connection->base_time = PHP_INT_MAX;

        $this->expectException(InvalidArgumentException::class);

        (new RouteCostService)->calculate($connection, 'rain');
    }

    private function connection(): Connection
    {
        return Connection::factory()->make([
            'origin_station_id' => 1,
            'destination_station_id' => 2,
            'base_time' => 4,
            'weather_penalty' => 1,
            'peak_hour_penalty' => 2,
            'congestion_penalty' => 1,
            'transfer_penalty' => 3,
        ]);
    }
}
