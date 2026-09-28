<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRouteRequest;
use App\Models\Connection;
use App\Models\Station;
use App\Services\DemoNetworkService;
use App\Services\RoutePlannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function index(Request $request, DemoNetworkService $demoNetwork): View
    {
        $form = [];
        foreach (['origin_station_id' => '', 'destination_station_id' => '', 'departure_time' => '09:00', 'weather' => 'normal', 'demo_routes' => '0', 'transfer_minutes' => '3'] as $field => $default) {
            $value = $request->old($field, $default);
            $form[$field] = is_string($value) || is_int($value) ? (string) $value : $default;
        }

        $stations = Station::orderBy('name')->orderBy('id')->get(['id', 'code', 'name']);

        $closedInput = $request->old('closed_connections', []);
        $form['closed_connections'] = is_array($closedInput) ? array_map('strval', array_filter($closedInput, fn ($value): bool => is_int($value) || is_string($value))) : [];
        $closedIds = array_map('intval', $request->session()->get('selection.closed_connections', []));

        $demoConnections = $demoNetwork->connections($stations);
        $demoEnabled = (bool) $request->session()->get('selection.demo_routes', false);
        $connections = Connection::orderBy('id')->get(['id', 'origin_station_id', 'destination_station_id', 'line', 'base_time']);
        $closureOptions = $connections->map(fn (Connection $connection): array => [
            'id' => $connection->id,
            'label' => $stations->firstWhere('id', $connection->origin_station_id)->name.' → '.$stations->firstWhere('id', $connection->destination_station_id)->name.' · '.$connection->line,
        ]);
        if ($demoEnabled) {
            $connections = $connections->concat($demoConnections);
        }

        return view('route-planner', [
            'closureOptions' => $closureOptions,
            'activeClosures' => $closureOptions->whereIn('id', $closedIds),
            'demoAvailable' => $demoConnections->isNotEmpty(),
            'demoEnabled' => $demoEnabled,
            'stations' => $stations,
            'mapData' => [
                'stations' => $stations->toArray(),
                'connections' => $connections->map(fn (Connection $connection): array => [...$connection->toArray(), 'closed' => in_array($connection->id, $closedIds, true)])->toArray(),
                'routeResults' => $request->session()->get('routeResults'),
            ],
            'form' => $form,
            'selection' => $request->session()->get('selection'),
            'routeResults' => $request->session()->get('routeResults'),
        ]);
    }

    public function prepare(PlanRouteRequest $request, RoutePlannerService $planner): RedirectResponse
    {
        $selection = $request->validated();
        if (isset($selection['closed_connections'])) {
            $selection['closed_connections'] = array_map('intval', $selection['closed_connections']);
        }
        $scenarios = $planner->compare(
            (int) $selection['origin_station_id'],
            (int) $selection['destination_station_id'],
            $selection['departure_time'],
            $selection['weather'],
            (bool) ($selection['demo_routes'] ?? false),
            isset($selection['transfer_minutes']) ? (int) $selection['transfer_minutes'] : null,
            $selection['closed_connections'] ?? [],
        );

        return to_route('routes.index')->withInput($selection)->with('selection', $selection)
            ->with('routeResults', ['selected' => 'trip', 'scenarios' => $scenarios]);
    }
}
