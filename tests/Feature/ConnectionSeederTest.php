<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use Database\Seeders\ConnectionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use LogicException;
use Tests\TestCase;

class ConnectionSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_default_seeding_connects_the_catalog_in_both_directions(): void
    {
        $this->seed();

        $this->assertDatabaseCount('connections', 40);
        $this->assertSame(28, Connection::where('line', 'A')->count());
        $this->assertSame(12, Connection::where('line', 'B')->count());
        foreach (Connection::all() as $connection) {
            $this->assertDatabaseHas('connections', [
                'origin_station_id' => $connection->destination_station_id,
                'destination_station_id' => $connection->origin_station_id,
                'line' => $connection->line,
                'base_time' => $connection->base_time,
            ]);
        }
        $this->assertSame(0, Station::doesntHave('outgoingConnections')->count());
        $sanAntonio = Station::where('code', 'san-antonio')->firstOrFail();
        $this->assertSame(['A', 'B'], $sanAntonio->outgoingConnections()->distinct()->orderBy('line')->pluck('line')->all());
    }

    public function test_reseeding_preserves_ids_and_restores_simulated_costs(): void
    {
        $this->seed();
        $ids = Connection::orderBy('id')->pluck('id')->all();
        $connection = Connection::where('line', 'A')->firstOrFail();
        $connection->update(['base_time' => 99]);

        $this->seed(ConnectionSeeder::class);

        $this->assertSame($ids, Connection::orderBy('id')->pluck('id')->all());
        $this->assertSame(4, $connection->refresh()->base_time);
    }

    public function test_missing_stations_fail_before_any_connections_are_written(): void
    {
        Station::factory()->create(['code' => 'niquia']);

        try {
            $this->seed(ConnectionSeeder::class);
            $this->fail('Expected a missing station error.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('StationSeeder', $exception->getMessage());
            $this->assertDatabaseCount('connections', 0);
        }
    }
}
