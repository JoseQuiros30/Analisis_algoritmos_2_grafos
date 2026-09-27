<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRouteRequest;
use App\Models\Station;
use App\Services\RoutePlannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function index(Request $request): View
    {
        $form = [];
        foreach (['origin_station_id' => '', 'destination_station_id' => '', 'departure_time' => '09:00', 'weather' => 'normal', 'is_peak_hour' => '0'] as $field => $default) {
            $value = $request->old($field, $default);
            $form[$field] = is_string($value) || is_int($value) ? (string) $value : $default;
        }

        return view('route-planner', [
            'stations' => Station::orderBy('name')->orderBy('id')->get(['id', 'name']),
            'form' => $form,
            'selection' => $request->session()->get('selection'),
            'routeResults' => $request->session()->get('routeResults'),
        ]);
    }

    public function prepare(PlanRouteRequest $request, RoutePlannerService $planner): RedirectResponse
    {
        $selection = $request->validated();
        $scenarios = $planner->compare((int) $selection['origin_station_id'], (int) $selection['destination_station_id']);
        $key = $selection['weather'] === 'rain' ? 'rain' : 'normal';
        if ($request->boolean('is_peak_hour')) {
            $key = $key === 'rain' ? 'rain_peak_hour' : 'peak_hour';
        }

        return to_route('routes.index')->withInput($selection)->with('selection', $selection)
            ->with('routeResults', ['selected' => $key, 'scenarios' => $scenarios]);
    }
}
