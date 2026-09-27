<?php

namespace App\Http\Requests;

use App\Models\Connection;
use App\Services\ScenarioComparisonService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompareScenariosRequest extends FormRequest
{
    protected $redirectRoute = 'scenarios.index';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'connection_id' => ['bail', 'sometimes', 'required', 'integer', Rule::exists(Connection::class, 'id')],
            'scenario' => ['bail', 'sometimes', 'required', 'string', Rule::in(array_keys(ScenarioComparisonService::presets()))],
        ];
    }

    public function messages(): array
    {
        return [
            'connection_id.required' => 'Selecciona una conexión.',
            'connection_id.integer' => 'Selecciona una conexión válida.',
            'connection_id.exists' => 'La conexión seleccionada ya no está disponible.',
            'scenario.required' => 'Selecciona un escenario.',
            'scenario.string' => 'Selecciona uno de los cuatro escenarios disponibles.',
            'scenario.in' => 'Selecciona uno de los cuatro escenarios disponibles.',
        ];
    }
}
