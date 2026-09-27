<?php

namespace App\Http\Requests;

use App\Models\Station;
use App\Services\RouteCostService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_station_id' => ['bail', 'required', 'integer', Rule::exists(Station::class, 'id')],
            'destination_station_id' => ['bail', 'required', 'integer', Rule::exists(Station::class, 'id'), 'different:origin_station_id'],
            'departure_time' => ['bail', 'required', 'string', 'date_format:H:i'],
            'weather' => ['bail', 'required', 'string', Rule::in([RouteCostService::WEATHER_NORMAL, RouteCostService::WEATHER_RAIN])],
            'is_peak_hour' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin_station_id.required' => 'Selecciona una estación de origen.',
            'origin_station_id.integer' => 'Selecciona una estación de origen válida.',
            'origin_station_id.exists' => 'La estación de origen ya no está disponible.',
            'destination_station_id.required' => 'Selecciona una estación de destino.',
            'destination_station_id.integer' => 'Selecciona una estación de destino válida.',
            'destination_station_id.exists' => 'La estación de destino ya no está disponible.',
            'destination_station_id.different' => 'El destino debe ser distinto al origen.',
            'departure_time.required' => 'Indica la hora de salida.',
            'departure_time.string' => 'Usa una hora válida en formato HH:MM.',
            'departure_time.date_format' => 'Usa una hora válida en formato HH:MM.',
            'weather.required' => 'Selecciona una condición climática.',
            'weather.string' => 'Selecciona Normal o Lluvia.',
            'weather.in' => 'Selecciona Normal o Lluvia.',
            'is_peak_hour.required' => 'Indica si deseas simular hora pico.',
            'is_peak_hour.boolean' => 'La simulación de hora pico debe estar activada o desactivada.',
        ];
    }
}
