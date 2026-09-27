<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRouteRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function index(Request $request): View
    {
        $form = [];
        foreach (['origin_station_id' => '', 'destination_station_id' => '', 'departure_time' => '09:00', 'weather' => 'normal'] as $field => $default) {
            $value = $request->old($field, $default);
            $form[$field] = is_string($value) || is_int($value) ? (string) $value : $default;
        }

        return view('route-planner', [
            'stations' => Station::orderBy('name')->orderBy('id')->get(['id', 'name']),
            'form' => $form,
            'selection' => $request->session()->get('selection'),
        ]);
    }

    public function prepare(PlanRouteRequest $request): RedirectResponse
    {
        $selection = $request->validated();

        return to_route('routes.index')->withInput($selection)->with('selection', $selection);
    }
}
