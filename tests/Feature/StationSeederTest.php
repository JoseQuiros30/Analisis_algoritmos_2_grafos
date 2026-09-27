<?php

namespace Tests\Feature;

use App\Models\Station;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StationSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_default_seeding_loads_the_representative_station_catalog(): void
    {
        $this->seed();

        $this->assertDatabaseCount('stations', 21);
        $this->assertDatabaseHas('stations', ['code' => 'niquia', 'name' => 'Niquía']);
        $this->assertDatabaseHas('stations', ['code' => 'san-antonio', 'name' => 'San Antonio']);
        $this->assertDatabaseHas('stations', ['code' => 'san-javier', 'name' => 'San Javier']);
    }

    public function test_reseeding_preserves_station_ids_and_does_not_duplicate_records(): void
    {
        $this->seed(StationSeeder::class);
        $stationIds = Station::orderBy('code')->pluck('id', 'code')->all();
        Station::where('code', 'niquia')->update(['name' => 'Nombre modificado']);

        $this->seed(StationSeeder::class);

        $this->assertSame($stationIds, Station::orderBy('code')->pluck('id', 'code')->all());
        $this->assertDatabaseHas('stations', ['code' => 'niquia', 'name' => 'Niquía']);
    }

    public function test_seeding_preserves_stations_outside_the_catalog(): void
    {
        $customStation = Station::factory()->create(['code' => 'estacion-adicional']);

        $this->seed(StationSeeder::class);

        $this->assertModelExists($customStation);
        $this->assertDatabaseCount('stations', 22);
    }
}
