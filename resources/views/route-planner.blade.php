<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Prepara un recorrido y explora el proyecto académico MetroRoute Medellín.">
    <title>Planifica tu recorrido · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 font-sans text-emerald-950 antialiased">
    <a href="#planner" class="sr-only focus:not-sr-only focus:block focus:p-4">Saltar al planificador</a>
    <header class="border-b border-emerald-900/10 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-5">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold tracking-tight"><span aria-hidden="true" class="grid size-10 place-items-center rounded-xl bg-emerald-900 text-lg text-lime-200">M</span> MetroRoute <span class="font-normal text-stone-500">/ Medellín</span></a>
            <a href="{{ route('scenarios.index') }}" class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 underline-offset-4 hover:underline">Comparar escenarios →</a>
        </div>
    </header>
    <main id="planner" class="mx-auto max-w-7xl px-6 py-10 lg:py-14">
        <div class="mb-9 flex flex-wrap items-end justify-between gap-4">
            <div><p class="mb-3 text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Tu ciudad, conexión a conexión</p><h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">Planea tu recorrido.</h1><p class="mt-4 max-w-xl leading-relaxed text-stone-600">Elige tus estaciones y las condiciones del viaje. Explora cómo cambia el costo de moverse por la ciudad.</p></div>
            <p class="text-sm text-stone-500">Datos simulados · Proyecto académico</p>
        </div>
        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900"><p class="font-semibold">Revisa los datos del recorrido.</p><ul class="mt-2 list-inside list-disc text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="grid items-start gap-6 lg:grid-cols-[310px_minmax(0,1fr)]">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm" aria-labelledby="configuration-title">
                <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-emerald-700">01 / Configurar</p>
                <h2 id="configuration-title" class="mb-6 text-xl font-semibold">¿A dónde vamos?</h2>
                @if ($stations->count() < 2)
                    <p role="status" class="mb-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Aún no hay suficientes estaciones disponibles para preparar un recorrido.</p>
                @endif
                <form method="POST" action="{{ route('routes.prepare') }}" id="route-planner-form" class="space-y-5">
                    @csrf
                    @foreach (['origin_station_id' => 'Origen', 'destination_station_id' => 'Destino'] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="mb-2 block text-sm font-semibold">{{ $label }}</label>
                            <select id="{{ $field }}" name="{{ $field }}" required class="planner-input" aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" @if($errors->has($field)) aria-describedby="{{ $field }}-error" @endif>
                                <option value="">Selecciona una estación</option>
                                @foreach ($stations as $station)
                                    <option value="{{ $station->id }}" @selected($form[$field] === (string) $station->id)>{{ $station->name }}</option>
                                @endforeach
                            </select>
                            @error($field)<p id="{{ $field }}-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <button type="button" id="swap-stations" hidden class="text-sm font-semibold text-emerald-700 underline decoration-emerald-200 underline-offset-4">↕ Intercambiar estaciones</button>
                    <div>
                        <label for="departure_time" class="mb-2 block text-sm font-semibold">Hora de salida <span class="font-normal text-stone-500">· Bogotá</span></label>
                        <input type="time" id="departure_time" name="departure_time" value="{{ $form['departure_time'] }}" required class="planner-input" aria-invalid="{{ $errors->has('departure_time') ? 'true' : 'false' }}" aria-describedby="departure-help">
                        <p id="departure-help" class="mt-2 text-xs leading-relaxed text-stone-500">Hora pico simulada: 06:00–08:59 y 16:00–18:59. Congestión alta en esas franjas y baja fuera de ellas.</p>
                        @error('departure_time')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <fieldset>
                        <legend class="mb-2 text-sm font-semibold">Clima</legend>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['normal' => 'Normal', 'rain' => 'Lluvia'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-stone-200 p-3 text-sm has-checked:border-emerald-700 has-checked:bg-emerald-50"><input type="radio" name="weather" value="{{ $value }}" required @checked($form['weather'] === $value) class="accent-emerald-800">{{ $label }}</label>
                            @endforeach
                        </div>
                        @error('weather')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </fieldset>
                    <div>
                        <label for="transfer_minutes" class="mb-2 block text-sm font-semibold">Minutos por cambio de línea</label>
                        <input id="transfer_minutes" name="transfer_minutes" type="number" min="0" max="60" step="1" required value="{{ $form['transfer_minutes'] }}" class="planner-input" aria-describedby="transfer-help">
                        <p id="transfer-help" class="mt-2 text-xs text-stone-500">Tiempo simulado. No se cobra al abordar la primera línea. Usa 0 para comparar sin penalización.</p>
                        @error('transfer_minutes')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    @if ($demoAvailable)
                        <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950">
                            <label class="flex items-start gap-2 font-semibold"><input type="checkbox" name="demo_routes" value="1" @checked($form['demo_routes'] === '1') class="mt-1 accent-violet-700"> Activar rutas alternativas de demostración</label>
                            <p class="mt-2 text-xs leading-relaxed">Añade una conexión ficticia Universidad ↔ San Antonio. Prueba ese trayecto a las 10:00: Normal usa el atajo (10 min); Lluvia usa la línea A (20 min). No representa un servicio real.</p>
                        </div>
                    @endif
                    <details class="rounded-xl border border-red-200 p-4" @if(count($form['closed_connections'])) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold">Simular incidentes / cierres</summary>
                        <p id="closures-help" class="mt-2 text-xs text-stone-600">Marca los sentidos que deseas cerrar. Para cerrar ambos sentidos, marca ambas direcciones. Solo afecta este cálculo, no modifica el catálogo. Desmarca y calcula de nuevo para reabrir.</p>
                        <div class="mt-3 max-h-64 space-y-3 overflow-y-auto" role="group" aria-describedby="closures-help" aria-label="Conexiones cerradas">
                            @forelse ($closureOptions as $option)
                                <label class="flex items-start gap-2 text-xs"><input type="checkbox" name="closed_connections[]" value="{{ $option['id'] }}" @checked(in_array((string) $option['id'], $form['closed_connections'], true)) class="mt-0.5 accent-red-700">{{ $option['label'] }}</label>
                            @empty
                                <p class="text-xs text-stone-500">No hay conexiones en el catálogo.</p>
                            @endforelse
                        </div>
                    </details>
                    <button type="submit" @disabled($stations->count() < 2) class="w-full rounded-xl bg-emerald-900 px-4 py-3.5 font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">Calcular ruta <span aria-hidden="true">→</span></button>
                    <p class="text-xs leading-relaxed text-stone-500">Calcula la ruta de menor costo para las condiciones seleccionadas y compara los cuatro escenarios.</p>
                </form>
            </section>
            @include('partials.metro-map')
            <aside class="rounded-3xl border border-stone-200 bg-white p-6 lg:col-start-1" aria-labelledby="summary-title">
                <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-emerald-700">03 / Tu recorrido</p>
                <h2 id="summary-title" class="text-xl font-semibold">Resumen</h2>
                @if ($selection)
                    <div id="validated-selection" class="mt-5" role="status">
                        <p class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">Selección validada</p>
                        <dl class="space-y-4 text-sm">
                            <div><dt class="text-stone-500">Origen</dt><dd class="mt-1 font-semibold">{{ $stations->firstWhere('id', $selection['origin_station_id'])?->name ?? 'Estación no disponible' }}</dd></div>
                            <div><dt class="text-stone-500">Destino</dt><dd class="mt-1 font-semibold">{{ $stations->firstWhere('id', $selection['destination_station_id'])?->name ?? 'Estación no disponible' }}</dd></div>
                            <div><dt class="text-stone-500">Salida · Bogotá</dt><dd class="mt-1 font-semibold">{{ $selection['departure_time'] }}</dd></div>
                            <div><dt class="text-stone-500">Clima</dt><dd class="mt-1 font-semibold">{{ $selection['weather'] === 'rain' ? 'Lluvia' : 'Normal' }}</dd></div>
                        </dl>
                    </div>
                @else
                    <p class="mt-5 text-sm leading-relaxed text-stone-600">Calcula una ruta para revisar las estaciones y condiciones seleccionadas.</p>
                @endif
                <p id="selection-changed" hidden role="status" class="mt-5 text-sm text-amber-800">Cambiaste las condiciones. Calcula la ruta otra vez para actualizar los resultados.</p>
                <p class="mt-6 border-t border-stone-100 pt-5 text-xs leading-relaxed text-stone-500">Hora pico y congestión se calculan según la salida. El tiempo por cambio de línea participa en la búsqueda de la ruta de menor costo.</p>
            </aside>
        </div>
        @if ($routeResults)
            @include('partials.route-results', ['routeResults' => $routeResults])
        @endif
        <footer class="mt-8 flex flex-wrap justify-between gap-3 border-t border-stone-200 pt-6 text-xs text-stone-500"><p>MetroRoute Medellín · Jose &amp; Anderson</p><p>Proyecto académico. No es un servicio oficial del Metro.</p></footer>
    </main>
</body>
</html>
