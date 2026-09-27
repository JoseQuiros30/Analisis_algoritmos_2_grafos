<?php

namespace Tests\Feature;

use App\Models\Station;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_saves_a_station_with_its_code_and_accented_name(): void
    {
        Station::create(['code' => 'niquia', 'name' => 'Niquía']);

        $this->assertDatabaseHas('stations', ['code' => 'niquia', 'name' => 'Niquía']);
    }

    public function test_rejects_two_stations_with_the_same_code(): void
    {
        Station::factory()->create(['code' => 'san-antonio']);

        $this->expectException(UniqueConstraintViolationException::class);

        Station::factory()->create(['code' => 'san-antonio']);
    }
}
