@php
    $selected = $routeResults['scenarios'][$routeResults['selected']];
@endphp
<section id="route-results" class="mt-8 space-y-6" aria-labelledby="route-results-title">
    <div class="rounded-3xl border border-stone-200 bg-white p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Resultado / {{ $selected['label'] }}</p>
        <h2 id="route-results-title" class="mt-3 text-2xl font-semibold">Tu ruta calculada</h2>
        @if (isset($selected['conditions']))
            <p class="mt-3 text-sm text-stone-600">Hora pico: {{ $selected['conditions']['is_peak_hour'] ? 'Sí' : 'No' }} · Congestión {{ $selected['conditions']['congestion_level'] === 'high' ? 'alta' : 'baja' }} · Condiciones simuladas.</p>
        @endif
        @if (! $selected['found'])
            <p role="status" class="mt-5 rounded-xl bg-amber-50 p-5 text-amber-900">No existe una ruta disponible entre estas estaciones en el sentido seleccionado.</p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-emerald-950 p-5 text-white"><p class="text-sm text-emerald-200">Tiempo total simulado</p><p class="mt-2 text-4xl font-semibold">{{ $selected['costs']['total'] }} <span class="text-lg">min</span></p></div>
                <div class="rounded-2xl bg-stone-50 p-5"><p class="text-sm text-stone-500">Estaciones, incluidos los extremos</p><p class="mt-2 text-4xl font-semibold">{{ count($selected['stations']) }}</p></div>
                <div class="rounded-2xl bg-stone-50 p-5"><p class="text-sm text-stone-500">Transbordos / cambios de línea</p><p class="mt-2 text-4xl font-semibold">{{ $selected['transfer_count'] }}</p></div>
            </div>
            <div class="mt-8 grid gap-8 md:grid-cols-2">
                <div>
                    <h3 class="font-semibold">Desglose del costo</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        @foreach (['base_time' => 'Tiempo base', 'weather_penalty' => 'Lluvia', 'peak_hour_penalty' => 'Hora pico', 'congestion_penalty' => 'Congestión', 'transfer_penalty' => 'Penalización por transbordo (pendiente)'] as $field => $label)
                            <div class="flex justify-between gap-4 border-b border-stone-100 pb-2"><dt>{{ $label }}</dt><dd class="shrink-0 font-semibold">{{ $selected['costs'][$field] }} min</dd></div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-xs leading-relaxed text-stone-500">La hora de salida determina hora pico y congestión para todo el recorrido. Cambiar de línea todavía no añade tiempo.</p>
                </div>
                <div>
                    <h3 class="font-semibold">Estación a estación</h3>
                    <ol class="mt-4 space-y-4 border-l-2 border-emerald-200 pl-5">
                        @foreach ($selected['stations'] as $index => $station)
                            <li><p class="font-medium">{{ $index + 1 }}. {{ $station['name'] }}</p>
                                @if (isset($selected['legs'][$index]))
                                    <p class="mt-1 text-sm text-stone-500">Línea {{ $selected['legs'][$index]['line'] }} · {{ $selected['legs'][$index]['costs']['total'] }} min hasta {{ $selected['legs'][$index]['destination'] }}</p>
                                    @if ($index > 0 && $selected['legs'][$index - 1]['line'] !== $selected['legs'][$index]['line'])
                                        <p class="mt-1 text-xs font-semibold text-emerald-700">Transbordo: línea {{ $selected['legs'][$index - 1]['line'] }} → {{ $selected['legs'][$index]['line'] }}</p>
                                    @endif
                                @else
                                    <p class="mt-1 text-sm text-emerald-700">Llegada</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endif
    </div>
    <div class="rounded-3xl border border-stone-200 bg-white p-6 sm:p-8">
        <h3 class="text-xl font-semibold">Comparación de rutas completas</h3>
        <p class="mt-2 text-sm leading-relaxed text-stone-600">Dijkstra se ejecuta de nuevo para cada escenario. Los cuatro escenarios de referencia excluyen congestión; tu selección incorpora el tráfico automático según la hora. Las diferencias se calculan respecto a Normal.</p>
        <div class="mt-5 overflow-x-auto" tabindex="0" role="region" aria-label="Comparación de rutas, desplazable horizontalmente">
            <table class="w-full min-w-[650px] text-left text-sm">
                <thead class="border-b border-stone-200 text-stone-500"><tr><th scope="col" class="p-3">Escenario</th><th scope="col" class="p-3">Total</th><th scope="col" class="p-3">Diferencia</th><th scope="col" class="p-3">Recorrido</th></tr></thead>
                <tbody>
                    @foreach ($routeResults['scenarios'] as $key => $result)
                        <tr @class(['border-b border-stone-100', 'bg-emerald-50' => $key === $routeResults['selected']])>
                            <th scope="row" class="p-3 font-medium">{{ $result['label'] }} @if($key === $routeResults['selected'])<span class="sr-only">(seleccionado)</span>@endif</th>
                            <td class="p-3 font-semibold">{{ $result['found'] ? $result['costs']['total'].' min' : 'Sin ruta' }}</td>
                            <td class="p-3">{{ $result['found'] && $routeResults['scenarios']['normal']['found'] ? '+'.($result['costs']['total'] - $routeResults['scenarios']['normal']['costs']['total']).' min' : '—' }}</td>
                            <td class="p-3">{{ $result['found'] ? implode(' → ', array_column($result['stations'], 'name')) : 'No hay conexión disponible' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-4 text-xs leading-relaxed text-stone-500">El catálogo actual tiene un único camino simple entre cada par conectado: los tiempos pueden cambiar sin que cambien las estaciones. Se necesitan conexiones alternativas para demostrar un cambio de ruta.</p>
    </div>
</section>
