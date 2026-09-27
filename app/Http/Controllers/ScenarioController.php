<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompareScenariosRequest;
use App\Models\Connection;
use App\Services\ScenarioComparisonService;
use Illuminate\View\View;

class ScenarioController extends Controller
{
    public function index(CompareScenariosRequest $request, ScenarioComparisonService $comparisonService): View
    {
        $data = $request->validated();
        $connections = Connection::with(['originStation:id,name', 'destinationStation:id,name'])
            ->orderBy('line')->orderBy('id')->get();
        $connection = isset($data['connection_id'])
            ? $connections->firstWhere('id', $data['connection_id'])
            : $connections->first();

        return view('scenarios', [
            'connections' => $connections,
            'connection' => $connection,
            'presets' => ScenarioComparisonService::presets(),
            'activeScenario' => $data['scenario'] ?? 'normal',
            'comparison' => $connection ? $comparisonService->compare($connection) : [],
        ]);
    }
}
