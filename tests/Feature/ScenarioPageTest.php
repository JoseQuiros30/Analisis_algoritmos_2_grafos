<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScenarioPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_displays_an_empty_state_without_connections(): void
    {
        $this->get(route('scenarios.index'))->assertOk()->assertSee('No hay conexiones disponibles');
    }

    public function test_defaults_to_the_first_connection_and_normal_scenario(): void
    {
        $connection = Connection::factory()->create();

        $this->get(route('scenarios.index'))->assertOk()
            ->assertViewHas('connection', fn (Connection $selected): bool => $selected->is($connection))
            ->assertViewHas('activeScenario', 'normal')
            ->assertSee('Un tramo. Cuatro escenarios.');
    }

    public function test_compares_the_selected_direction_without_changing_database_costs(): void
    {
        $first = Connection::factory()->create(['base_time' => 9]);
        $reverse = Connection::factory()->create([
            'origin_station_id' => $first->destination_station_id,
            'destination_station_id' => $first->origin_station_id,
            'base_time' => 3, 'weather_penalty' => 2, 'peak_hour_penalty' => 4,
        ]);

        $this->get(route('scenarios.index', ['connection_id' => $reverse->id, 'scenario' => 'rain_peak_hour']))
            ->assertOk()->assertViewHas('activeScenario', 'rain_peak_hour')
            ->assertViewHas('connection', fn (Connection $selected): bool => $selected->is($reverse))
            ->assertViewHas('comparison', fn (array $rows): bool => $rows['rain_peak_hour']['costs']['total'] === 9 && $rows['rain_peak_hour']['difference'] === 6)
            ->assertSee('no el tiempo de una ruta completa');
        $this->assertSame(3, $reverse->refresh()->base_time);
        $this->assertSame(9, $first->refresh()->base_time);
        $this->assertDatabaseCount('connections', 2);
    }

    #[DataProvider('invalidSelections')]
    public function test_rejects_invalid_selections_without_a_redirect_loop(array $query, string $message): void
    {
        $this->followingRedirects()->get(route('scenarios.index', $query))
            ->assertOk()->assertSee($message);
    }

    public static function invalidSelections(): array
    {
        return [
            'missing connection' => [['connection_id' => 999999], 'La conexión seleccionada ya no está disponible.'],
            'empty connection' => [['connection_id' => ''], 'Selecciona una conexión.'],
            'array connection' => [['connection_id' => ['bad']], 'Selecciona una conexión válida.'],
            'unknown scenario' => [['scenario' => 'storm'], 'Selecciona uno de los cuatro escenarios disponibles.'],
            'array scenario' => [['scenario' => ['rain']], 'Selecciona uno de los cuatro escenarios disponibles.'],
            'empty scenario' => [['scenario' => ''], 'Selecciona un escenario.'],
        ];
    }

    public function test_escapes_station_names_in_connection_options(): void
    {
        $origin = Station::factory()->create(['name' => '<script>alert(1)</script>']);
        Connection::factory()->for($origin, 'originStation')->create();

        $this->get(route('scenarios.index'))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
