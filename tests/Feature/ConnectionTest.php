<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConnectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_relates_each_direction_to_the_correct_stations(): void
    {
        $origin = Station::factory()->create();
        $destination = Station::factory()->create();
        $forward = Connection::factory()->for($origin, 'originStation')->for($destination, 'destinationStation')->create();
        $reverse = Connection::factory()->for($destination, 'originStation')->for($origin, 'destinationStation')->create();

        $this->assertTrue($forward->originStation->is($origin));
        $this->assertTrue($forward->destinationStation->is($destination));
        $this->assertSame([$forward->id], $origin->outgoingConnections->modelKeys());
        $this->assertSame([$reverse->id], $origin->incomingConnections->modelKeys());
    }

    public function test_rejects_duplicate_connections_on_the_same_line_and_direction(): void
    {
        $connection = Connection::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Connection::factory()->create($connection->only(['origin_station_id', 'destination_station_id', 'line']));
    }

    public function test_allows_the_same_stations_to_be_connected_by_another_line(): void
    {
        $connection = Connection::factory()->create(['line' => 'A']);

        Connection::factory()->create([
            ...$connection->only(['origin_station_id', 'destination_station_id']),
            'line' => 'B',
        ]);

        $this->assertDatabaseCount('connections', 2);
    }

    public function test_rejects_a_connection_to_the_same_station(): void
    {
        $station = Station::factory()->create();

        $this->expectException(ValidationException::class);

        Connection::factory()->for($station, 'originStation')->for($station, 'destinationStation')->create();
    }

    public function test_rejects_a_reference_to_a_missing_station(): void
    {
        $this->expectException(QueryException::class);

        Connection::factory()->create(['destination_station_id' => 999999]);
    }

    public function test_prevents_deleting_a_station_used_by_a_connection(): void
    {
        $connection = Connection::factory()->create();

        $this->expectException(QueryException::class);

        $connection->destinationStation->delete();
    }

    #[DataProvider('invalidCosts')]
    public function test_rejects_invalid_costs(string $field, int|float $value): void
    {
        $this->expectException(ValidationException::class);

        Connection::factory()->create([$field => $value]);
    }

    public static function invalidCosts(): array
    {
        return [
            'zero base time' => ['base_time', 0],
            'negative base time' => ['base_time', -1],
            'fractional base time' => ['base_time', 1.5],
            'negative weather' => ['weather_penalty', -1],
            'negative peak hour' => ['peak_hour_penalty', -1],
            'negative congestion' => ['congestion_penalty', -1],
            'negative transfer' => ['transfer_penalty', -1],
        ];
    }

    public function test_defaults_penalties_to_zero_when_omitted(): void
    {
        $origin = Station::factory()->create();
        $destination = Station::factory()->create();

        $connection = Connection::create([
            'origin_station_id' => $origin->id,
            'destination_station_id' => $destination->id,
            'line' => 'A',
            'base_time' => 4,
        ])->refresh();

        $this->assertSame([
            'base_time' => 4,
            'weather_penalty' => 0,
            'peak_hour_penalty' => 0,
            'congestion_penalty' => 0,
            'transfer_penalty' => 0,
        ], $connection->only(['base_time', 'weather_penalty', 'peak_hour_penalty', 'congestion_penalty', 'transfer_penalty']));
    }
}
