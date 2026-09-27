<?php

namespace App\Services;

class DijkstraService
{
    /**
     * Calcula las distancias desde el origen y reconstruye la ruta al destino.
     *
     * Contrato provisional de integracion: cada clave es una estacion y sus
     * vecinos contienen pesos finales, calculados previamente por RouteCostService
     * cuando este disponible. Incluir todos los nodos, incluso los aislados.
     * Para conexiones bidireccionales se deben incluir ambos sentidos.
     * Las conexiones cerradas deben omitirse antes de llamar al servicio.
     * Seleccion lineal del menor: O(V^2 + E) tiempo. Espacio auxiliar O(V)
     * sin historial y O(V^2 + E) con capturas de cada iteracion.
     * Las distancias inalcanzables se devuelven como null para permitir JSON.
     * Cada paso representa el estado DESPUES de evaluar todos los vecinos.
     * Se procesan todos los nodos alcanzables, incluso despues del destino.
     *
     * @param  array<int|string, array<int|string, int|float>>  $graph
     * @return array{
     *     path: list<int|string>, totalCost: float|null,
     *     distances: array<int|string, float|null>,
     *     predecessors: array<int|string, int|string|null>, visited: list<int|string>,
     *     steps: list<array{
     *         iteration: int, currentNode: int|string, currentDistance: float,
     *         neighbors: list<array{node: int|string, weight: int|float, previousDistance: float|null, candidateDistance: float|null, newDistance: float|null, status: 'relaxed'|'unchanged'|'visited'}>,
     *         distances: array<int|string, float|null>,
     *         predecessors: array<int|string, int|string|null>, visited: list<int|string>
     *     }>
     * }
     */
    public function findShortestPath(array $graph, int|string $origin, int|string $destination, bool $recordSteps = true): array
    {
        $this->validateGraph($graph, $origin, $destination);

        $distances = array_fill_keys(array_keys($graph), INF);
        $predecessors = array_fill_keys(array_keys($graph), null);
        $visited = [];
        $steps = [];
        $distances[$origin] = 0.0;

        while (true) {
            $current = null;
            $minimumDistance = INF;

            foreach ($distances as $node => $distance) {
                if (! isset($visited[$node]) && $distance < $minimumDistance) {
                    $current = $node;
                    $minimumDistance = $distance;
                }
            }

            if ($current === null) {
                break;
            }

            $visited[$current] = true;
            $neighbors = [];

            foreach ($graph[$current] as $neighbor => $weight) {
                $previousDistance = $distances[$neighbor];

                if (isset($visited[$neighbor])) {
                    if ($recordSteps) {
                        $neighbors[] = [
                            'node' => $neighbor,
                            'weight' => $weight,
                            'previousDistance' => $previousDistance,
                            'candidateDistance' => null,
                            'newDistance' => $previousDistance,
                            'status' => 'visited',
                        ];
                    }

                    continue;
                }

                $candidate = $distances[$current] + $weight;

                if (! is_finite($candidate)) {
                    throw new \OverflowException('El costo acumulado excede el rango numerico.');
                }

                if ($candidate < $distances[$neighbor]) {
                    $distances[$neighbor] = $candidate;
                    $predecessors[$neighbor] = $current;
                }

                if ($recordSteps) {
                    $neighbors[] = [
                        'node' => $neighbor,
                        'weight' => $weight,
                        'previousDistance' => is_finite($previousDistance) ? $previousDistance : null,
                        'candidateDistance' => $candidate,
                        'newDistance' => $distances[$neighbor],
                        'status' => $candidate < $previousDistance ? 'relaxed' : 'unchanged',
                    ];
                }
            }

            if ($recordSteps) {
                $steps[] = [
                    'iteration' => count($steps) + 1,
                    'currentNode' => $current,
                    'currentDistance' => $distances[$current],
                    'neighbors' => $neighbors,
                    'distances' => $this->serializableDistances($distances),
                    'predecessors' => $predecessors,
                    'visited' => array_keys($visited),
                ];
            }
        }

        $path = [];

        if (is_finite($distances[$destination])) {
            for ($node = $destination; $node !== null; $node = $predecessors[$node]) {
                $path[] = $node;
            }

            $path = array_reverse($path);
        }

        $distances = $this->serializableDistances($distances);

        return [
            'path' => $path,
            'totalCost' => $distances[$destination],
            'distances' => $distances,
            'predecessors' => $predecessors,
            'visited' => array_keys($visited),
            'steps' => $steps,
        ];
    }

    /**
     * @param  array<int|string, float>  $distances
     * @return array<int|string, float|null>
     */
    private function serializableDistances(array $distances): array
    {
        foreach ($distances as $node => $distance) {
            $distances[$node] = is_finite($distance) ? $distance : null;
        }

        return $distances;
    }

    /**
     * @param  array<int|string, array<int|string, int|float>>  $graph
     */
    private function validateGraph(array $graph, int|string $origin, int|string $destination): void
    {
        if (! array_key_exists($origin, $graph) || ! array_key_exists($destination, $graph)) {
            throw new \InvalidArgumentException('El origen y el destino deben existir en el grafo.');
        }

        foreach ($graph as $neighbors) {
            if (! is_array($neighbors)) {
                throw new \InvalidArgumentException('Los vecinos deben ser una lista de adyacencia.');
            }

            foreach ($neighbors as $neighbor => $weight) {
                if (! array_key_exists($neighbor, $graph)) {
                    throw new \InvalidArgumentException('Cada vecino debe existir en el grafo.');
                }

                if ((! is_int($weight) && ! is_float($weight)) || ! is_finite((float) $weight) || $weight < 0) {
                    throw new \InvalidArgumentException('Los pesos deben ser numeros finitos no negativos.');
                }
            }
        }
    }
}
