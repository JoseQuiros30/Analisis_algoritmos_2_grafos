<div class="rounded-3xl border border-stone-200 bg-white p-6 sm:p-8" aria-labelledby="dijkstra-title">
    <h3 id="dijkstra-title" class="text-xl font-semibold">Dijkstra paso a paso</h3>
    <p class="mt-2 text-sm leading-relaxed text-stone-600">Cada iteración fija el estado pendiente de menor distancia y evalúa sus conexiones. Un estado combina estación y línea de llegada para considerar los transbordos. ∞ significa que aún no se conoce un camino. Los costos son minutos acumulados desde el origen.</p>
    <p class="mt-2 text-xs text-stone-500">“Llegada” une las líneas del destino con costo cero; no es una estación adicional. El algoritmo continúa hasta procesar todos los estados alcanzables. Complejidad de esta implementación: O(V² + E), donde V cuenta estados y E sus conexiones.</p>
    <div class="mt-5 space-y-3">
        @forelse ($result['steps'] ?? [] as $step)
            <details class="rounded-xl border border-stone-200 p-4">
                <summary class="cursor-pointer font-semibold">Paso {{ $step['iteration'] }} · {{ $result['node_labels'][$step['currentNode']] ?? $step['currentNode'] }} · {{ $step['currentDistance'] }} min</summary>
                <p class="mt-3 text-sm text-stone-600">Estados fijados: {{ count($step['visited']) }}. Se evalúan {{ count($step['neighbors']) }} conexiones salientes.</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($step['neighbors'] as $neighbor)
                        <li><strong>{{ $result['node_labels'][$neighbor['node']] ?? $neighbor['node'] }}</strong> · peso {{ $neighbor['weight'] }} min · anterior {{ $neighbor['previousDistance'] ?? '∞' }} → {{ $neighbor['newDistance'] ?? '∞' }}.
                            @if ($neighbor['status'] === 'visited') Ya fijado.
                            @elseif ($neighbor['status'] === 'relaxed') Mejora: {{ $step['currentDistance'] }} + {{ $neighbor['weight'] }} = {{ $neighbor['candidateDistance'] }}.
                            @else Sin mejora: candidato {{ $neighbor['candidateDistance'] }}.
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 overflow-x-auto" tabindex="0" role="region" aria-label="Distancias y predecesores del paso {{ $step['iteration'] }}">
                    <table class="w-full min-w-[540px] text-left text-sm">
                        <caption class="pb-2 text-left font-semibold">Estado después de esta iteración</caption>
                        <thead><tr><th scope="col" class="p-2">Estación / línea</th><th scope="col" class="p-2">Distancia</th><th scope="col" class="p-2">Predecesor</th><th scope="col" class="p-2">Fijado</th></tr></thead>
                        <tbody>
                            @foreach ($step['distances'] as $node => $distance)
                                <tr class="border-t border-stone-100"><th scope="row" class="p-2 font-medium">{{ $result['node_labels'][$node] ?? $node }}</th><td class="p-2">{{ $distance ?? '∞' }}</td><td class="p-2">{{ $result['node_labels'][$step['predecessors'][$node]] ?? '—' }}</td><td class="p-2">{{ in_array($node, $step['visited'], true) ? 'Sí' : 'No' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @empty
            <p class="text-sm text-stone-500">No hay iteraciones registradas para este resultado.</p>
        @endforelse
    </div>
</div>
