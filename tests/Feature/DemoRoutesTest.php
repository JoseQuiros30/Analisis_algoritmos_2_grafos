<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use App\Services\RoutePlannerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DemoRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_switches_paths_with_weather_in_both_directions_without_changing_the_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);
        $before = Connection::orderBy('id')->get()->toArray();
        $origin = Station::where('code', 'universidad')->firstOrFail()->id;
        $destination = Station::where('code', 'san-antonio')->firstOrFail()->id;
        $planner = app(RoutePlannerService::class);

        foreach ([[$origin, $destination], [$destination, $origin]] as [$from, $to]) {
            $normal = $planner->compare($from, $to, '10:00', 'normal', true)['trip'];
            $rain = $planner->compare($from, $to, '10:00', 'rain', true)['trip'];
            $base = $planner->compare($from, $to, '10:00')['trip'];
            $this->assertSame(10, $normal['costs']['total']);
            $this->assertSame(['DEMO'], array_column($normal['legs'], 'line'));
            $this->assertSame([$from, $to], array_column($normal['stations'], 'id'));
            $this->assertSame(20, $rain['costs']['total']);
            $this->assertSame(['A', 'A', 'A', 'A'], array_column($rain['legs'], 'line'));
            $this->assertSame(16, $base['costs']['total']);
        }

        $this->assertSame($before, Connection::orderBy('id')->get()->toArray());
    }

    public function test_form_map_and_results_share_demo_connections_and_can_return_to_base_mode(): void
    {
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
        $input = [
            'origin_station_id' => Station::where('code', 'universidad')->firstOrFail()->id,
            'destination_station_id' => Station::where('code', 'san-antonio')->firstOrFail()->id,
            'departure_time' => '07:30', 'weather' => 'normal',
        ];

        $this->followingRedirects()->post(route('routes.prepare'), [...$input, 'demo_routes' => '1'])
            ->assertSee('Conexión ficticia violeta')
            ->assertViewHas('demoEnabled', true)
            ->assertViewHas('mapData', function (array $data): bool {
                $trip = $data['routeResults']['scenarios']['trip'];
                $ids = array_column($data['connections'], 'id');

                return count($ids) === 42 && in_array($trip['legs'][0]['connection_id'], $ids, true)
                    && $trip['legs'][0]['line'] === 'DEMO' && $trip['costs']['total'] === 14;
            });

        $this->followingRedirects()->post(route('routes.prepare'), $input)
            ->assertViewHas('demoEnabled', false)
            ->assertViewHas('mapData', fn (array $data): bool => count($data['connections']) === 40
                && $data['routeResults']['scenarios']['trip']['costs']['total'] === 32);
        $this->assertDatabaseCount('connections', 40);
    }

    public function test_invalid_demo_option_is_rejected(): void
    {
        $connection = Connection::factory()->create();

        $this->post(route('routes.prepare'), [
            'origin_station_id' => $connection->origin_station_id,
            'destination_station_id' => $connection->destination_station_id,
            'departure_time' => '10:00', 'weather' => 'normal', 'demo_routes' => 'yes',
        ])->assertSessionHasErrors(['demo_routes' => 'Selecciona un modo demostrativo válido.']);
    }

    public function test_demo_is_unavailable_without_its_stations(): void
    {
        $this->withoutVite();
        $connection = Connection::factory()->create();

        $this->get(route('routes.index'))->assertViewHas('demoAvailable', false);
        $this->post(route('routes.prepare'), [
            'origin_station_id' => $connection->origin_station_id,
            'destination_station_id' => $connection->destination_station_id,
            'departure_time' => '10:00', 'weather' => 'normal', 'demo_routes' => '1',
        ])->assertSessionHasErrors(['demo_routes' => 'La demostración requiere las estaciones Universidad y San Antonio.']);
    }
}
