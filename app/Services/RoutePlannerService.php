<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class RoutePlannerService
{
    public function __construct(
        private RouteCostService $costService,
        private DijkstraService $dijkstraService,
    ) {}

    /**
     * Use the same network snapshot for all scenarios. Congestion and transfer
     * penalties remain disabled until their domain rules are integrated.
     *
     * @return array<string, array<string, mixed>>
     */
    public function compare(int $origin, int $destination): array
    {
        $stations = Station::orderBy('id')->get(['id', 'name'])->keyBy('id');
        $connections = Connection::orderBy('id')->get();

        if (! $stations->has($origin) || ! $stations->has($destination)) {
            throw new InvalidArgumentException('El origen y el destino deben existir.');
        }

        $results = [];
        foreach (ScenarioComparisonService::presets() as $key => $preset) {
            $results[$key] = [
                ...$preset,
                ...$this->calculate($stations, $connections, $origin, $destination, $preset['weather'], $preset['is_peak_hour']),
            ];
        }

        return $results;
    }

    /**
     * Dijkstra accepts one weight per neighbor. Keep the cheapest parallel
     * connection and its identity so reconstruction uses the same edge.
     * Equal weights retain the lowest connection ID.
     *
     * @param  Collection<int, Station>  $stations
     * @param  Collection<int, Connection>  $connections
     * @return array{found: bool, stations: list<array{id: int, name: string}>, legs: list<array<string, mixed>>, costs: array<string, int>|null, transfer_count: int}
     */
    private function calculate(Collection $stations, Collection $connections, int $origin, int $destination, string $weather, bool $isPeakHour): array
    {
        $graph = array_fill_keys($stations->modelKeys(), []);
        $edges = [];

        foreach ($connections as $connection) {
            $from = $connection->origin_station_id;
            $to = $connection->destination_station_id;
            $costs = $this->costService->calculate($connection, $weather, $isPeakHour);

            if (! isset($graph[$from][$to]) || $costs['total'] < $graph[$from][$to]) {
                $graph[$from][$to] = $costs['total'];
                $edges[$from][$to] = [
                    'connection_id' => $connection->id,
                    'origin' => $stations[$from]->name,
                    'destination' => $stations[$to]->name,
                    'line' => $connection->line,
                    'costs' => $costs,
                ];
            }
        }

        $route = $this->dijkstraService->findShortestPath($graph, $origin, $destination, recordSteps: false);
        if ($route['totalCost'] === null) {
            return ['found' => false, 'stations' => [], 'legs' => [], 'costs' => null, 'transfer_count' => 0];
        }

        $legs = [];
        $totals = array_fill_keys(['base_time', 'weather_penalty', 'peak_hour_penalty', 'congestion_penalty', 'transfer_penalty', 'total'], 0);
        $transfers = 0;
        $previousLine = null;

        for ($index = 0; $index < count($route['path']) - 1; $index++) {
            $leg = $edges[$route['path'][$index]][$route['path'][$index + 1]];
            if ($previousLine !== null && $previousLine !== $leg['line']) {
                $transfers++;
            }
            $previousLine = $leg['line'];
            $legs[] = $leg;
            foreach ($leg['costs'] as $field => $minutes) {
                if ($totals[$field] > PHP_INT_MAX - $minutes) {
                    throw new \OverflowException('El costo acumulado excede el rango de minutos enteros.');
                }
                $totals[$field] += $minutes;
            }
        }

        return [
            'found' => true,
            'stations' => array_map(fn (int $id): array => ['id' => $id, 'name' => $stations[$id]->name], $route['path']),
            'legs' => $legs,
            'costs' => $totals,
            'transfer_count' => $transfers,
        ];
    }
}
