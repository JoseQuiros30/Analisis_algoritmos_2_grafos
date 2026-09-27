<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use App\Services\RoutePlannerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RoutePlannerIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_uses_dijkstra_to_choose_a_different_route_when_rain_changes_the_weights(): void
    {
        [$origin, $middle, $destination] = Station::factory()->count(3)->create()->all();
        $direct = $this->connect($origin, $destination, ['base_time' => 3, 'weather_penalty' => 10]);
        $first = $this->connect($origin, $middle, ['base_time' => 2, 'weather_penalty' => 0]);
        $second = $this->connect($middle, $destination, ['base_time' => 2, 'weather_penalty' => 0]);

        $results = app(RoutePlannerService::class)->compare($origin->id, $destination->id);

        $this->assertSame([$direct->id], array_column($results['normal']['legs'], 'connection_id'));
        $this->assertSame([$first->id, $second->id], array_column($results['rain']['legs'], 'connection_id'));
        $this->assertSame(3, $results['normal']['costs']['total']);
        $this->assertSame(4, $results['rain']['costs']['total']);
        $this->assertSame(10, $direct->refresh()->weather_penalty);
    }

    public function test_chooses_the_cheapest_parallel_edge_and_retains_its_line_and_costs(): void
    {
        [$origin, $destination] = Station::factory()->count(2)->create()->all();
        $this->connect($origin, $destination, ['line' => 'A', 'base_time' => 8]);
        $best = $this->connect($origin, $destination, ['line' => 'B', 'base_time' => 3]);
        $this->connect($origin, $destination, ['line' => 'C', 'base_time' => 3]);

        $result = app(RoutePlannerService::class)->compare($origin->id, $destination->id)['normal'];

        $this->assertSame($best->id, $result['legs'][0]['connection_id']);
        $this->assertSame('B', $result['legs'][0]['line']);
        $this->assertSame(3, $result['costs']['total']);
    }

    public function test_counts_line_changes_without_charging_an_unimplemented_transfer_rule(): void
    {
        [$origin, $middle, $destination] = Station::factory()->count(3)->create()->all();
        $this->connect($origin, $middle, ['line' => 'A', 'base_time' => 4, 'weather_penalty' => 1, 'peak_hour_penalty' => 2]);
        $this->connect($middle, $destination, ['line' => 'B', 'base_time' => 3, 'weather_penalty' => 2, 'peak_hour_penalty' => 1, 'transfer_penalty' => 9, 'congestion_penalty' => 8]);

        $result = app(RoutePlannerService::class)->compare($origin->id, $destination->id)['rain_peak_hour'];

        $this->assertSame(1, $result['transfer_count']);
        $this->assertSame([$origin->id, $middle->id, $destination->id], array_column($result['stations'], 'id'));
        $this->assertSame([
            'base_time' => 7, 'weather_penalty' => 3, 'peak_hour_penalty' => 3,
            'congestion_penalty' => 0, 'transfer_penalty' => 0, 'total' => 13,
        ], $result['costs']);
    }

    public function test_does_not_invent_reverse_edges_or_routes_to_isolated_stations(): void
    {
        [$origin, $destination, $isolated] = Station::factory()->count(3)->create()->all();
        $this->connect($origin, $destination);
        $service = app(RoutePlannerService::class);

        $reverse = $service->compare($destination->id, $origin->id)['normal'];
        $unreachable = $service->compare($origin->id, $isolated->id)['normal'];

        $this->assertFalse($reverse['found']);
        $this->assertNull($reverse['costs']);
        $this->assertSame([], $reverse['legs']);
        $this->assertSame($reverse, $unreachable);
    }

    public function test_seeded_network_produces_a_complete_route_and_http_summary(): void
    {
        $this->withoutVite();
        $this->seed();
        $origin = Station::where('code', 'niquia')->firstOrFail();
        $destination = Station::where('code', 'san-javier')->firstOrFail();

        $response = $this->post(route('routes.prepare'), [
            'origin_station_id' => $origin->id, 'destination_station_id' => $destination->id,
            'departure_time' => '07:30', 'weather' => 'rain', 'is_peak_hour' => '1',
        ]);

        $response->assertRedirectToRoute('routes.index')->assertSessionHasNoErrors();
        $response->assertSessionHas('routeResults', function (array $results): bool {
            $selected = $results['scenarios'][$results['selected']];

            return $results['selected'] === 'rain_peak_hour'
                && $selected['costs']['total'] === 85
                && count($selected['stations']) === 14
                && $selected['transfer_count'] === 1;
        });
        $this->get(route('routes.index'))->assertOk()->assertSee('Tu ruta calculada')
            ->assertSee('Comparación de rutas completas')->assertSee('Llegada')->assertSee('Niquía')->assertSee('San Javier');
    }

    public function test_rejects_invalid_peak_hour_flags(): void
    {
        $this->withoutVite();
        [$origin, $destination] = Station::factory()->count(2)->create()->all();

        $this->post(route('routes.prepare'), [
            'origin_station_id' => $origin->id, 'destination_station_id' => $destination->id,
            'departure_time' => '07:30', 'weather' => 'normal', 'is_peak_hour' => 'yes',
        ])->assertSessionHasErrors(['is_peak_hour']);
    }

    private function connect(Station $origin, Station $destination, array $costs = []): Connection
    {
        return Connection::factory()->for($origin, 'originStation')->for($destination, 'destinationStation')->create($costs);
    }
}
