<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Connection> */
class ConnectionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'origin_station_id' => Station::factory(),
            'destination_station_id' => Station::factory(),
            'line' => 'A',
            'base_time' => 4,
            'weather_penalty' => 1,
            'peak_hour_penalty' => 2,
            'congestion_penalty' => 1,
            'transfer_penalty' => 0,
        ];
    }
}
