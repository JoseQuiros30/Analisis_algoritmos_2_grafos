<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MetroMapTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_real_stations_and_directed_connections_to_the_map(): void
    {
        $this->withoutVite();
        $connection = Connection::factory()->create();

        $this->get(route('routes.index'))->assertOk()
            ->assertSee('Tu recorrido en el mapa')
            ->assertViewHas('mapData', function (array $data) use ($connection): bool {
                return count($data['stations']) === 2
                    && $data['connections'][0]['id'] === $connection->id
                    && $data['connections'][0]['origin_station_id'] === $connection->origin_station_id
                    && $data['connections'][0]['destination_station_id'] === $connection->destination_station_id
                    && $data['routeResults'] === null;
            });
    }

    public function test_map_json_cannot_inject_html_from_a_station_name(): void
    {
        $this->withoutVite();
        Station::factory()->create(['name' => '</script><script>alert(1)</script>']);

        $this->get(route('routes.index'))->assertOk()
            ->assertDontSee('</script><script>alert(1)</script>', false)
            ->assertSee('\\u003C', false);
    }

    public function test_map_receives_the_same_results_as_the_route_summary(): void
    {
        $this->withoutVite();
        $connection = Connection::factory()->create();
        $this->followingRedirects()->post(route('routes.prepare'), [
            'origin_station_id' => $connection->origin_station_id,
            'destination_station_id' => $connection->destination_station_id,
            'departure_time' => '09:00', 'weather' => 'rain',
        ])->assertOk()->assertViewHas('mapData', function (array $data) use ($connection): bool {
            $result = $data['routeResults']['scenarios']['rain'];

            return $result['found'] && $result['legs'][0]['connection_id'] === $connection->id
                && $result['costs']['total'] === 5;
        });
    }
}
