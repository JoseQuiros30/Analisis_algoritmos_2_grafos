<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use App\Services\RoutePlannerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ConnectionClosuresTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_closure_reroutes_all_scenarios_without_modifying_the_catalog(): void
    {
        $this->withoutVite();
        $this->seed();
        $origin = Station::where('code', 'universidad')->firstOrFail();
        $destination = Station::where('code', 'san-antonio')->firstOrFail();
        $closed = Connection::where('origin_station_id', $origin->id)->where('line', 'A')->orderByDesc('destination_station_id')->firstOrFail();
        $before = Connection::orderBy('id')->get()->toArray();

        $this->followingRedirects()->post(route('routes.prepare'), [
            'origin_station_id' => $origin->id, 'destination_station_id' => $destination->id,
            'departure_time' => '10:00', 'weather' => 'rain', 'transfer_minutes' => 3,
            'demo_routes' => 1, 'closed_connections' => [(string) $closed->id],
        ])->assertSee('Cierres simulados aplicados (1)')->assertViewHas('mapData', function (array $data) use ($closed): bool {
            foreach ($data['routeResults']['scenarios'] as $route) {
                if (! $route['found'] || array_column($route['legs'], 'line') !== ['DEMO']) {
                    return false;
                }
            }
            $closures = array_values(array_filter($data['connections'], fn (array $edge): bool => $edge['closed']));

            return array_column($closures, 'id') === [$closed->id]
                && $data['routeResults']['scenarios']['trip']['costs']['total'] === 25;
        });
        $this->assertSame($before, Connection::orderBy('id')->get()->toArray());
    }

    public function test_closing_one_direction_leaves_reverse_open_and_unchecking_reopens_it(): void
    {
        $this->withoutVite();
        $forward = Connection::factory()->create();
        $reverse = Connection::factory()->create([
            'origin_station_id' => $forward->destination_station_id,
            'destination_station_id' => $forward->origin_station_id,
        ]);
        $input = ['origin_station_id' => $forward->origin_station_id,
            'destination_station_id' => $forward->destination_station_id,
            'departure_time' => '10:00', 'weather' => 'normal'];

        $this->followingRedirects()->post(route('routes.prepare'), [...$input, 'closed_connections' => [$forward->id]])
            ->assertSee('No existe una ruta disponible')->assertSee('Dijkstra paso a paso');
        $result = app(RoutePlannerService::class)->compare($reverse->origin_station_id, $reverse->destination_station_id, closedConnectionIds: [$forward->id]);
        $this->assertSame([$reverse->id], array_column($result['normal']['legs'], 'connection_id'));
        $this->followingRedirects()->post(route('routes.prepare'), $input)
            ->assertViewHas('activeClosures', fn ($closures): bool => $closures->isEmpty())
            ->assertViewHas('routeResults', fn (array $results): bool => $results['scenarios']['trip']['found']);
    }

    public function test_closing_one_parallel_connection_keeps_the_other_available(): void
    {
        $closed = Connection::factory()->create(['line' => 'A', 'base_time' => 1]);
        $open = Connection::factory()->create([
            'origin_station_id' => $closed->origin_station_id,
            'destination_station_id' => $closed->destination_station_id,
            'line' => 'B', 'base_time' => 5,
        ]);

        $result = app(RoutePlannerService::class)->compare($closed->origin_station_id, $closed->destination_station_id, closedConnectionIds: [$closed->id]);

        $this->assertSame([$open->id], array_column($result['normal']['legs'], 'connection_id'));
        $this->assertSame(5, $result['normal']['costs']['total']);
    }

    public function test_rejects_malformed_unknown_duplicate_and_virtual_closures(): void
    {
        $connection = Connection::factory()->create();
        foreach (['bad', [-1], [999999], [$connection->id, $connection->id], [['nested']], ['key' => $connection->id]] as $closures) {
            $response = $this->post(route('routes.prepare'), [
                'origin_station_id' => $connection->origin_station_id,
                'destination_station_id' => $connection->destination_station_id,
                'departure_time' => '10:00', 'weather' => 'normal', 'closed_connections' => $closures,
            ]);
            $response->assertSessionHasErrors();
            $response->assertSessionMissing('routeResults');
        }
    }
}
