<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ConnectionSeeder extends Seeder
{
    /**
     * Seed a simplified academic network with simulated travel times.
     */
    public function run(): void
    {
        $lines = [
            'A' => ['niquia', 'bello', 'acevedo', 'universidad', 'hospital', 'prado', 'parque-berrio', 'san-antonio', 'alpujarra', 'industriales', 'poblado', 'aguacatala', 'ayura', 'envigado', 'itagui'],
            'B' => ['san-antonio', 'cisneros', 'suramericana', 'estadio', 'floresta', 'santa-lucia', 'san-javier'],
        ];
        $stationIds = Station::pluck('id', 'code');

        foreach ($lines as $codes) {
            foreach ($codes as $code) {
                if (! $stationIds->has($code)) {
                    throw new LogicException('Falta la estación '.$code.'. Ejecuta StationSeeder antes de ConnectionSeeder.');
                }
            }
        }

        DB::transaction(function () use ($lines, $stationIds): void {
            foreach ($lines as $line => $codes) {
                for ($index = 0; $index < count($codes) - 1; $index++) {
                    $origin = $stationIds[$codes[$index]];
                    $destination = $stationIds[$codes[$index + 1]];

                    foreach ([[$origin, $destination], [$destination, $origin]] as [$from, $to]) {
                        Connection::updateOrCreate([
                            'origin_station_id' => $from,
                            'destination_station_id' => $to,
                            'line' => $line,
                        ], [
                            'base_time' => $line === 'A' ? 4 : 3,
                            'weather_penalty' => 1,
                            'peak_hour_penalty' => 2,
                            'congestion_penalty' => 1,
                            'transfer_penalty' => 0,
                        ]);
                    }
                }
            }
        });
    }
}
