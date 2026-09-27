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
        <div class="grid items-start gap-6 lg:grid-cols-[350px_minmax(0,1fr)] xl:grid-cols-[330px_minmax(0,1fr)_280px]">
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
                        <p id="departure-help" class="mt-2 text-xs leading-relaxed text-stone-500">Hora de referencia. La detección automática de hora pico aún no está integrada; utiliza el control de simulación.</p>
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
                        <input type="hidden" name="is_peak_hour" value="0">
                        <label class="flex items-center gap-3 text-sm font-semibold"><input type="checkbox" name="is_peak_hour" value="1" @checked($form['is_peak_hour'] === '1') class="accent-emerald-800"> Simular hora pico</label>
                        @error('is_peak_hour')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" @disabled($stations->count() < 2) class="w-full rounded-xl bg-emerald-900 px-4 py-3.5 font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">Calcular ruta <span aria-hidden="true">→</span></button>
                    <p class="text-xs leading-relaxed text-stone-500">Calcula la ruta de menor costo para las condiciones seleccionadas y compara los cuatro escenarios.</p>
                </form>
            </section>
            <section class="overflow-hidden rounded-3xl bg-emerald-950 text-white" aria-labelledby="network-title">
                <div class="p-7 sm:p-8">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-lime-200">02 / Explorar</p>
                    <h2 id="network-title" class="text-2xl font-semibold">Una red de posibilidades.</h2>
                    <p class="mt-3 text-sm leading-relaxed text-emerald-100/80">Cada estación es un vértice. Cada conexión, una arista cuyo costo depende de las condiciones del recorrido.</p>
                    <div class="my-7 rounded-2xl border border-white/15 bg-white/5 p-6">
                        <p class="text-xs uppercase tracking-widest text-emerald-200">Visualización del grafo</p>
                        <p class="mt-3 text-lg font-medium">Próximamente</p>
                        <p class="mt-2 text-sm leading-relaxed text-emerald-100/75">Aquí podrás explorar las conexiones y ver la ruta calculada.</p>
                    </div>
                    <div class="flex items-center justify-between gap-3"><h3 class="text-sm font-semibold">Estaciones del catálogo</h3><span class="rounded-full bg-lime-200 px-3 py-1 text-xs font-bold text-emerald-950">{{ $stations->count() }}</span></div>
                    <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-xs text-emerald-50 sm:text-sm">
                        @forelse ($stations as $station)<li class="flex items-start gap-2"><span aria-hidden="true" class="mt-1.5 size-1.5 shrink-0 rounded-full bg-lime-200"></span>{{ $station->name }}</li>@empty<li class="col-span-2">El catálogo estará disponible pronto.</li>@endforelse
                    </ul>
                    <p class="mt-6 text-xs leading-relaxed text-emerald-200/75">Catálogo alfabético de una selección académica. No indica el orden del recorrido.</p>
                </div>
            </section>
            <aside class="rounded-3xl border border-stone-200 bg-white p-6 lg:col-span-2 xl:col-span-1" aria-labelledby="summary-title">
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
                <p class="mt-6 border-t border-stone-100 pt-5 text-xs leading-relaxed text-stone-500">Congestión y penalización por transbordo no incluidas. Los transbordos se cuentan como cambios de línea.</p>
            </aside>
        </div>
        @if ($routeResults)
            @include('partials.route-results', ['routeResults' => $routeResults])
        @endif
        <footer class="mt-8 flex flex-wrap justify-between gap-3 border-t border-stone-200 pt-6 text-xs text-stone-500"><p>MetroRoute Medellín · Jose &amp; Anderson</p><p>Proyecto académico. No es un servicio oficial del Metro.</p></footer>
    </main>
</body>
</html>
