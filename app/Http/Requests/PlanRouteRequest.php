<?php

namespace App\Http\Requests;

use App\Models\Connection;
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
            'closed_connections' => ['bail', 'sometimes', 'array', 'list', 'max:1000'],
            'closed_connections.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists(Connection::class, 'id')],
            'demo_routes' => ['bail', 'sometimes', 'boolean', function (string $attribute, mixed $value, \Closure $fail): void {
                if ((bool) $value && Station::whereIn('code', ['universidad', 'san-antonio'])->count() !== 2) {
                    $fail('La demostración requiere las estaciones Universidad y San Antonio.');
                }
            }],
            'origin_station_id' => ['bail', 'required', 'integer', Rule::exists(Station::class, 'id')],
            'destination_station_id' => ['bail', 'required', 'integer', Rule::exists(Station::class, 'id'), 'different:origin_station_id'],
            'transfer_minutes' => ['sometimes', 'required', 'integer', 'between:0,60'],
            'departure_time' => ['bail', 'required', 'string', 'date_format:H:i'],
            'weather' => ['bail', 'required', 'string', Rule::in([RouteCostService::WEATHER_NORMAL, RouteCostService::WEATHER_RAIN])],
        ];
    }

    public function messages(): array
    {
        return [
            'closed_connections.array' => 'Selecciona una lista válida de conexiones para cerrar.',
            'closed_connections.list' => 'Selecciona una lista válida de conexiones para cerrar.',
            'closed_connections.max' => 'Puedes cerrar hasta 1000 conexiones por simulación.',
            'closed_connections.*.required' => 'Selecciona una conexión para cerrar.',
            'closed_connections.*.integer' => 'Selecciona una conexión válida para cerrar.',
            'closed_connections.*.exists' => 'La conexión seleccionada para cerrar no existe en el catálogo.',
            'closed_connections.*.distinct' => 'No repitas conexiones en la lista de cierres.',
            'transfer_minutes.*' => 'Indica un tiempo de transbordo entero entre 0 y 60 minutos.',
            'demo_routes.boolean' => 'Selecciona un modo demostrativo válido.',
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
        ];
    }
}
