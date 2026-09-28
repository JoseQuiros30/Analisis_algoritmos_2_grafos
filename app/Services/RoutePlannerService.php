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
        private TrafficCostService $trafficCosts,
        private TrafficConditionService $trafficConditions,
        private DemoNetworkService $demoNetwork,
    ) {}

    /**
     * Use the same network snapshot for the four reference scenarios and,
     * when a departure time is provided, the actual trip with automatic traffic.
     * Reference scenarios exclude congestion. Transfers are evaluated before optimization.
     *
     * @param  list<int>  $closedConnectionIds
     * @return array<string, array<string, mixed>>
     */
    public function compare(int $origin, int $destination, ?string $departureTime = null, string $weather = RouteCostService::WEATHER_NORMAL, bool $demoRoutes = false, ?int $transferMinutes = null, array $closedConnectionIds = []): array
    {
        if ($transferMinutes !== null && ($transferMinutes < 0 || $transferMinutes > 60)) {
            throw new InvalidArgumentException('El transbordo debe estar entre 0 y 60 minutos.');
        }

        $stations = Station::orderBy('id')->get(['id', 'code', 'name'])->keyBy('id');
        $connections = Connection::orderBy('id')->get();
        if ($demoRoutes) {
            $connections = $connections->concat($this->demoNetwork->connections($stations));
        }

        $connections = $connections->reject(fn (Connection $connection): bool => in_array($connection->id, $closedConnectionIds, true))->values();

        if (! $stations->has($origin) || ! $stations->has($destination)) {
            throw new InvalidArgumentException('El origen y el destino deben existir.');
        }

        $results = [];
        foreach (ScenarioComparisonService::presets() as $key => $preset) {
            $results[$key] = [
                ...$preset,
                ...$this->calculate($stations, $connections, $origin, $destination, $preset['weather'], $preset['is_peak_hour'], transferMinutes: $transferMinutes),
            ];
        }

        if ($departureTime !== null) {
            $results['trip'] = [
                'label' => 'Tu selección (tráfico automático)',
                'weather' => $weather,
                'conditions' => $this->trafficConditions->evaluate($departureTime),
                ...$this->calculate($stations, $connections, $origin, $destination, $weather, false, $departureTime, $transferMinutes),
            ];
        }

        return $results;
    }

    /**
     * Expand each station by arrival line so transfer costs participate in
     * Dijkstra relaxation. A zero-cost terminal joins all destination states.
     * Parallel connections within a state retain the cheapest edge.
     *
     * @param  Collection<int, Station>  $stations
     * @param  Collection<int, Connection>  $connections
     * @return array{found: bool, stations: list<array{id: int, name: string}>, legs: list<array<string, mixed>>, costs: array<string, int>|null, transfer_count: int, steps: list<array<string, mixed>>, node_labels: array<string, string>}
     */
    private function calculate(Collection $stations, Collection $connections, int $origin, int $destination, string $weather, bool $isPeakHour, ?string $departureTime = null, ?int $transferMinutes = null): array
    {
        $start = json_encode([$origin, null], JSON_THROW_ON_ERROR);
        $terminal = 'destination';
        $states = [$start => ['station' => $origin, 'line' => null]];
        foreach ($connections as $connection) {
            $key = json_encode([$connection->destination_station_id, $connection->line], JSON_THROW_ON_ERROR);
            $states[$key] = ['station' => $connection->destination_station_id, 'line' => $connection->line];
        }
        $graph = array_fill_keys(array_keys($states), []);
        $graph[$terminal] = [];
        $labels = [$terminal => 'Llegada a '.$stations[$destination]->name];
        $edges = [];
        $outgoing = $connections->groupBy('origin_station_id');

        foreach ($states as $from => $state) {
            $labels[$from] = $stations[$state['station']]->name.' · '.($state['line'] === null ? 'inicio' : 'línea '.$state['line']);
            if ($state['station'] === $destination) {
                $graph[$from][$terminal] = 0;
            }
            foreach ($outgoing->get($state['station'], []) as $storedConnection) {
                $connection = clone $storedConnection;
                if ($transferMinutes !== null) {
                    $connection->transfer_penalty = $transferMinutes;
                }
                $isTransfer = $state['line'] !== null && $state['line'] !== $connection->line;
                $to = json_encode([$connection->destination_station_id, $connection->line], JSON_THROW_ON_ERROR);
                $costs = $departureTime === null
                    ? $this->costService->calculate($connection, $weather, $isPeakHour, isTransfer: $isTransfer)
                    : $this->trafficCosts->calculate($connection, $departureTime, $weather, isTransfer: $isTransfer)['costs'];

                if (! isset($graph[$from][$to]) || $costs['total'] < $graph[$from][$to]) {
                    $graph[$from][$to] = $costs['total'];
                    $edges[$from][$to] = [
                        'connection_id' => $connection->id,
                        'origin' => $stations[$state['station']]->name,
                        'destination' => $stations[$connection->destination_station_id]->name,
                        'line' => $connection->line,
                        'costs' => $costs,
                    ];
                }
            }
        }

        $route = $this->dijkstraService->findShortestPath($graph, $start, $terminal, recordSteps: $departureTime !== null);
        if ($route['totalCost'] === null) {
            return ['found' => false, 'stations' => [], 'legs' => [], 'costs' => null, 'transfer_count' => 0, 'steps' => $route['steps'], 'node_labels' => $labels];
        }
        array_pop($route['path']);

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
            'stations' => array_map(fn (string $key): array => ['id' => $states[$key]['station'], 'name' => $stations[$states[$key]['station']]->name], $route['path']),
            'legs' => $legs,
            'costs' => $totals,
            'transfer_count' => $transfers,
            'steps' => $route['steps'],
            'node_labels' => $labels,
        ];
    }
}
