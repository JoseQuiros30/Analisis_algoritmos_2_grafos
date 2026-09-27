<?php

namespace Tests\Feature;

use App\Models\Station;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoutePlannerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_displays_stations_and_the_pending_calculation_notice(): void
    {
        Station::factory()->create(['name' => 'Niquía']);
        Station::factory()->create(['name' => 'San Javier']);

        $this->get(route('routes.index'))->assertOk()
            ->assertSee('Niquía')->assertSee('San Javier')
            ->assertSee('Cálculo pendiente de integración.');
    }

    public function test_disables_preparation_when_the_catalog_has_fewer_than_two_stations(): void
    {
        $this->get(route('routes.index'))->assertOk()
            ->assertSee('Aún no hay suficientes estaciones')
            ->assertSee('type="submit" disabled', false);
    }

    public function test_validates_a_selection_without_claiming_a_calculated_route(): void
    {
        $payload = $this->validPayload();

        $this->post(route('routes.prepare'), [...$payload, 'total' => 999])
            ->assertRedirectToRoute('routes.index')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('selection', $payload);
        $this->get(route('routes.index'))->assertOk()
            ->assertSee('Selección validada')->assertSee('07:30')
            ->assertSee('Cálculo pendiente de integración.');
        $this->assertDatabaseCount('stations', 2);
    }

    public function test_requires_all_controls(): void
    {
        $this->from(route('routes.index'))->post(route('routes.prepare'), [])
            ->assertRedirectToRoute('routes.index')
            ->assertSessionHasErrors(['origin_station_id', 'destination_station_id', 'departure_time', 'weather']);
    }

    public function test_rejects_the_same_origin_and_destination(): void
    {
        $payload = $this->validPayload();
        $payload['destination_station_id'] = $payload['origin_station_id'];

        $this->post(route('routes.prepare'), $payload)
            ->assertSessionHasErrors(['destination_station_id' => 'El destino debe ser distinto al origen.']);
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_controls_and_renders_the_form_again(string $field, mixed $value, string $message): void
    {
        $payload = $this->validPayload();
        $payload[$field] = $value;

        $this->followingRedirects()->from(route('routes.index'))
            ->post(route('routes.prepare'), $payload)
            ->assertOk()->assertSee($message);
    }

    public static function invalidInputs(): array
    {
        return [
            'missing origin' => ['origin_station_id', 999999, 'La estación de origen ya no está disponible.'],
            'missing destination' => ['destination_station_id', 999999, 'La estación de destino ya no está disponible.'],
            'invalid origin type' => ['origin_station_id', ['bad'], 'Selecciona una estación de origen válida.'],
            'invalid destination type' => ['destination_station_id', 'invalid', 'Selecciona una estación de destino válida.'],
            'hour out of range' => ['departure_time', '25:00', 'Usa una hora válida en formato HH:MM.'],
            'hour array' => ['departure_time', ['bad'], 'Usa una hora válida en formato HH:MM.'],
            'unknown weather' => ['weather', 'storm', 'Selecciona Normal o Lluvia.'],
            'weather array' => ['weather', ['bad'], 'Selecciona Normal o Lluvia.'],
        ];
    }

    public function test_escapes_station_names_in_the_page(): void
    {
        Station::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->get(route('routes.index'))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function validPayload(): array
    {
        $stations = Station::factory()->count(2)->create();

        return [
            'origin_station_id' => $stations[0]->id,
            'destination_station_id' => $stations[1]->id,
            'departure_time' => '07:30',
            'weather' => 'rain',
        ];
    }
}
