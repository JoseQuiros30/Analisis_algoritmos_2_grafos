<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;

class DemoNetworkService
{
    /**
     * Virtual connections use negative IDs and are never persisted.
     *
     * @param  Collection<int, Station>  $stations
     * @return Collection<int, Connection>
     */
    public function connections(Collection $stations): Collection
    {
        $origin = $stations->firstWhere('code', 'universidad');
        $destination = $stations->firstWhere('code', 'san-antonio');
        $connections = new Collection;

        if ($origin === null || $destination === null) {
            return $connections;
        }

        foreach ([[$origin->id, $destination->id], [$destination->id, $origin->id]] as $index => [$from, $to]) {
            $connection = new Connection([
                'origin_station_id' => $from,
                'destination_station_id' => $to,
                'line' => 'DEMO',
                'base_time' => 10,
                'weather_penalty' => 15,
                'peak_hour_penalty' => 2,
                'congestion_penalty' => 1,
                'transfer_penalty' => 0,
            ]);
            $connection->id = -1 - $index;
            $connections->push($connection);
        }

        return $connections;
    }
}
