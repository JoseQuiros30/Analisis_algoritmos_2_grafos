<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use App\Services\RoutePlannerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TransferRoutingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_keeps_a_more_expensive_arrival_line_when_it_avoids_a_later_transfer(): void
    {
        [$origin, $middle, $destination] = Station::factory()->count(3)->create()->all();
        $cheap = $this->connect($origin, $middle, 'A', 1);
        $sameLine = $this->connect($origin, $middle, 'B', 3);
        $last = $this->connect($middle, $destination, 'B', 1);
        $planner = app(RoutePlannerService::class);

        $with = $planner->compare($origin->id, $destination->id, '10:00', transferMinutes: 5)['trip'];
        $without = $planner->compare($origin->id, $destination->id, '10:00', transferMinutes: 0)['trip'];

        $this->assertSame([$sameLine->id, $last->id], array_column($with['legs'], 'connection_id'));
        $this->assertSame(4, $with['costs']['total']);
        $this->assertSame(0, $with['transfer_count']);
        $this->assertSame([$cheap->id, $last->id], array_column($without['legs'], 'connection_id'));
        $this->assertSame(2, $without['costs']['total']);
        $this->assertSame(1, $without['transfer_count']);
    }

    public function test_http_transfers_match_map_totals_and_academic_history_without_database_changes(): void
    {
        $this->withoutVite();
        [$origin, $middle, $destination] = Station::factory()->count(3)->create()->all();
        $this->connect($origin, $middle, 'A', 2);
        $last = $this->connect($middle, $destination, 'B', 2);

        $this->followingRedirects()->post(route('routes.prepare'), [
            'origin_station_id' => $origin->id, 'destination_station_id' => $destination->id,
            'departure_time' => '10:00', 'weather' => 'normal', 'transfer_minutes' => '3',
        ])->assertSee('Dijkstra paso a paso')->assertSee('Predecesor')->assertSee('Mejora:')
            ->assertViewHas('mapData', function (array $data): bool {
                $trip = $data['routeResults']['scenarios']['trip'];
                $finalStep = $trip['steps'][array_key_last($trip['steps'])];

                return $trip['costs']['total'] === 7 && $trip['costs']['transfer_penalty'] === 3
                    && $trip['legs'][0]['costs']['transfer_penalty'] === 0
                    && $trip['legs'][1]['costs']['transfer_penalty'] === 3
                    && $finalStep['distances']['destination'] === 7.0;
            });
        $this->assertSame(9, $last->refresh()->transfer_penalty);
    }

    public function test_charges_each_change_including_returning_to_a_previous_line(): void
    {
        [$origin, $first, $second, $destination] = Station::factory()->count(4)->create()->all();
        $this->connect($origin, $first, 'A', 1);
        $this->connect($first, $second, 'B', 1);
        $this->connect($second, $destination, 'A', 1);

        $result = app(RoutePlannerService::class)->compare($origin->id, $destination->id, transferMinutes: 3)['normal'];

        $this->assertSame(2, $result['transfer_count']);
        $this->assertSame(6, $result['costs']['transfer_penalty']);
        $this->assertSame(9, $result['costs']['total']);
    }

    public function test_rejects_invalid_transfer_minutes(): void
    {
        $connection = Connection::factory()->create();
        foreach ([-1, 61, '1.5', 'invalid'] as $value) {
            $this->post(route('routes.prepare'), [
                'origin_station_id' => $connection->origin_station_id,
                'destination_station_id' => $connection->destination_station_id,
                'departure_time' => '10:00', 'weather' => 'normal', 'transfer_minutes' => $value,
            ])->assertSessionHasErrors(['transfer_minutes' => 'Indica un tiempo de transbordo entero entre 0 y 60 minutos.']);
        }
    }

    public function test_unreachable_destination_still_renders_academic_history(): void
    {
        $this->withoutVite();
        [$origin, $destination] = Station::factory()->count(2)->create()->all();

        $this->followingRedirects()->post(route('routes.prepare'), [
            'origin_station_id' => $origin->id, 'destination_station_id' => $destination->id,
            'departure_time' => '10:00', 'weather' => 'normal', 'transfer_minutes' => 3,
        ])->assertSee('No existe una ruta disponible')->assertSee('Dijkstra paso a paso')->assertSee('∞');
    }

    private function connect(Station $origin, Station $destination, string $line, int $baseTime): Connection
    {
        return Connection::factory()->for($origin, 'originStation')->for($destination, 'destinationStation')->create([
            'line' => $line, 'base_time' => $baseTime, 'weather_penalty' => 0,
            'peak_hour_penalty' => 0, 'congestion_penalty' => 0, 'transfer_penalty' => 9,
        ]);
    }
}
