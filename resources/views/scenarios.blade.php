<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Compara cómo la lluvia y la hora pico cambian el costo simulado de una conexión del Metro.">
    <title>Comparar escenarios · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 font-sans text-emerald-950 antialiased">
    <a href="#comparison" class="sr-only focus:not-sr-only focus:block focus:p-4">Saltar a la comparación</a>
    <header class="border-b border-emerald-900/10 bg-white">
        <nav aria-label="Navegación principal" class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-5">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold"><span aria-hidden="true" class="grid size-10 place-items-center rounded-xl bg-emerald-900 text-lime-200">M</span> MetroRoute / Medellín</a>
            <a href="{{ route('routes.index') }}" class="text-sm font-semibold text-emerald-800 underline underline-offset-4">← Volver al planificador</a>
        </nav>
    </header>
    <main id="comparison" class="mx-auto max-w-7xl px-6 py-10 lg:py-14">
        <p class="mb-3 text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Laboratorio / Pesos dinámicos</p>
        <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">Un tramo. Cuatro escenarios.</h1>
        <p class="mt-4 max-w-2xl leading-relaxed text-stone-600">Compara el costo de una conexión bajo distintas condiciones. Son minutos simulados de un solo tramo, no el tiempo de una ruta completa.</p>
        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-900">
                <p class="font-semibold">Revisa la selección. Se muestra la comparación predeterminada.</p>
                <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @if ($connection)
            <form method="GET" action="{{ route('scenarios.index') }}" class="mt-8 rounded-3xl border border-stone-200 bg-white p-6 sm:p-8">
                <label for="connection_id" class="mb-2 block text-sm font-semibold">Conexión y sentido del viaje</label>
                <select id="connection_id" name="connection_id" required class="planner-input">
                    @foreach ($connections as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $connection->id)>{{ $option->originStation->name }} → {{ $option->destinationStation->name }} · Línea {{ $option->line }}</option>
                    @endforeach
                </select>
                <fieldset class="mt-6">
                    <legend class="mb-3 text-sm font-semibold">Escenario a destacar</legend>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($presets as $key => $preset)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-stone-200 p-4 text-sm font-semibold has-checked:border-emerald-700 has-checked:bg-emerald-50">
                                <input type="radio" name="scenario" value="{{ $key }}" @checked($activeScenario === $key) class="accent-emerald-800">{{ $preset['label'] }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                    <p class="max-w-xl text-xs leading-relaxed text-stone-500">Hora pico se activa como condición de prueba. No se deduce de una hora del reloj. Congestión y transbordo están desactivados en esta comparación.</p>
                    <button type="submit" class="rounded-xl bg-emerald-900 px-6 py-3 font-semibold text-white hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700">Comparar escenarios →</button>
                </div>
            </form>
            <section class="mt-6 grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]" aria-labelledby="results-title">
                <div class="rounded-3xl bg-emerald-950 p-7 text-white">
                    <p class="text-xs font-semibold uppercase tracking-widest text-lime-200">{{ $comparison[$activeScenario]['label'] }}</p>
                    <h2 id="results-title" class="mt-4 text-xl font-semibold">{{ $connection->originStation->name }} → {{ $connection->destinationStation->name }}</h2>
                    <p class="mt-2 text-sm text-emerald-200">Línea {{ $connection->line }} · Un solo tramo</p>
                    <p class="mt-8 text-6xl font-semibold tracking-tight">{{ $comparison[$activeScenario]['costs']['total'] }} <span class="text-lg font-normal">min</span></p>
                    <p class="mt-3 text-sm text-lime-200">+{{ $comparison[$activeScenario]['difference'] }} min frente al escenario normal</p>
                    <p class="mt-8 text-xs leading-relaxed text-emerald-100/75">Costo = tiempo base + lluvia + hora pico. Estos valores no cambian los datos guardados de la conexión.</p>
                </div>
                <div class="min-w-0 rounded-3xl border border-stone-200 bg-white p-6 sm:p-8">
                    <h3 class="text-xl font-semibold">¿De dónde sale el tiempo?</h3>
                    <div class="mt-5 overflow-x-auto" tabindex="0" role="region" aria-label="Tabla de comparación, desplazable horizontalmente">
                        <table class="w-full min-w-[550px] text-left text-sm">
                            <caption class="pb-4 text-left text-xs text-stone-500">Todos los valores están en minutos. La diferencia se calcula respecto a Normal.</caption>
                            <thead class="border-b border-stone-200 text-stone-500"><tr><th scope="col" class="py-3 pr-4">Escenario</th><th scope="col" class="p-3">Base</th><th scope="col" class="p-3">Lluvia</th><th scope="col" class="p-3">Hora pico</th><th scope="col" class="p-3">Total</th><th scope="col" class="p-3">Diferencia</th></tr></thead>
                            <tbody>
                                @foreach ($comparison as $key => $row)
                                    <tr @class(['border-b border-stone-100', 'bg-emerald-50' => $key === $activeScenario])>
                                        <th scope="row" class="py-4 pr-4 font-medium">{{ $row['label'] }} @if ($key === $activeScenario)<span class="sr-only">(seleccionado)</span>@endif</th>
                                        <td class="p-3">{{ $row['costs']['base_time'] }}</td><td class="p-3">{{ $row['costs']['weather_penalty'] }}</td><td class="p-3">{{ $row['costs']['peak_hour_penalty'] }}</td><td class="p-3 font-bold">{{ $row['costs']['total'] }}</td><td class="p-3">+{{ $row['difference'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-5 text-xs leading-relaxed text-stone-500">Dos escenarios pueden tener el mismo costo si sus penalizaciones son cero. Esta comparación no busca caminos ni comprueba cuál ruta es mejor.</p>
                </div>
            </section>
        @else
            <section class="mt-8 rounded-3xl border border-stone-200 bg-white p-8" role="status">
                <h2 class="text-xl font-semibold">No hay conexiones disponibles</h2><p class="mt-3 text-stone-600">La comparación estará disponible cuando se carguen las conexiones del catálogo.</p>
            </section>
        @endif
        <footer class="mt-8 border-t border-stone-200 pt-6 text-xs leading-relaxed text-stone-500">MetroRoute Medellín · Proyecto académico de Jose y Anderson. Datos simulados, no oficiales. La comparación de rutas completas estará disponible al integrar el cálculo de rutas.</footer>
    </main>
</body>
</html>
