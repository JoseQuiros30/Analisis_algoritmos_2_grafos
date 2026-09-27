<?php

namespace Tests\Unit\Services;

use App\Services\DijkstraService;
use InvalidArgumentException;
use OverflowException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DijkstraServiceTest extends TestCase
{
    public function test_reconstructs_the_cheapest_path_after_relaxing_an_existing_distance(): void
    {
        $graph = ['A' => ['B' => 10, 'C' => 2], 'B' => ['D' => 1], 'C' => ['B' => 3, 'D' => 20], 'D' => [], 'E' => []];

        $result = (new DijkstraService)->findShortestPath($graph, 'A', 'D');

        $this->assertSame([
            'path' => ['A', 'C', 'B', 'D'],
            'totalCost' => 6.0,
            'distances' => ['A' => 0.0, 'B' => 5.0, 'C' => 2.0, 'D' => 6.0, 'E' => null],
            'predecessors' => ['A' => null, 'B' => 'C', 'C' => 'A', 'D' => 'B', 'E' => null],
            'visited' => ['A', 'C', 'B', 'D'],
        ], $result);
    }

    public function test_returns_no_route_when_destination_is_unreachable(): void
    {
        $result = (new DijkstraService)->findShortestPath(['A' => ['B' => 1], 'B' => []], 'B', 'A');

        $this->assertSame([], $result['path']);
        $this->assertNull($result['totalCost']);
        $this->assertSame(['B'], $result['visited']);
    }

    public function test_returns_zero_cost_when_origin_equals_destination(): void
    {
        $result = (new DijkstraService)->findShortestPath([0 => []], 0, 0);

        $this->assertSame([0], $result['path']);
        $this->assertSame(0.0, $result['totalCost']);
    }

    public function test_handles_zero_weight_cycles_and_decimal_weights(): void
    {
        $graph = [0 => [1 => 0, 2 => 5], 1 => [0 => 0, 2 => 1.5], 2 => []];

        $result = (new DijkstraService)->findShortestPath($graph, 0, 2);

        $this->assertSame([0, 1, 2], $result['path']);
        $this->assertSame(1.5, $result['totalCost']);
    }

    public function test_changes_route_when_weights_change_or_an_edge_is_removed(): void
    {
        $service = new DijkstraService;
        $graph = ['A' => ['B' => 1, 'C' => 3], 'B' => ['D' => 1], 'C' => ['D' => 1], 'D' => []];
        $normal = $service->findShortestPath($graph, 'A', 'D');
        $graph['B']['D'] = 10;
        $congested = $service->findShortestPath($graph, 'A', 'D');
        unset($graph['A']['C']);
        $closed = $service->findShortestPath($graph, 'A', 'D');

        $this->assertSame(['A', 'B', 'D'], $normal['path']);
        $this->assertSame(['A', 'C', 'D'], $congested['path']);
        $this->assertSame(['A', 'B', 'D'], $closed['path']);
        $this->assertSame(11.0, $closed['totalCost']);
    }

    public function test_keeps_the_first_route_when_costs_are_equal(): void
    {
        $graph = ['A' => ['B' => 1, 'C' => 1], 'B' => ['D' => 1], 'C' => ['D' => 1], 'D' => []];

        $result = (new DijkstraService)->findShortestPath($graph, 'A', 'D');

        $this->assertSame(['A', 'B', 'D'], $result['path']);
        $this->assertSame(2.0, $result['totalCost']);
    }

    #[DataProvider('invalidGraphs')]
    public function test_rejects_invalid_graphs(array $graph, string $origin, string $destination, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new DijkstraService)->findShortestPath($graph, $origin, $destination);
    }

    public static function invalidGraphs(): array
    {
        return [
            'empty graph' => [[], 'A', 'B', 'El origen y el destino'],
            'missing origin' => [['A' => []], 'B', 'A', 'El origen y el destino'],
            'missing destination' => [['A' => []], 'A', 'B', 'El origen y el destino'],
            'invalid adjacency' => [['A' => null], 'A', 'A', 'Los vecinos'],
            'missing neighbor' => [['A' => ['B' => 1]], 'A', 'A', 'Cada vecino'],
            'negative weight' => [['A' => ['B' => -1], 'B' => []], 'A', 'B', 'Los pesos'],
            'infinite weight' => [['A' => ['B' => INF], 'B' => []], 'A', 'B', 'Los pesos'],
            'nan weight' => [['A' => ['B' => NAN], 'B' => []], 'A', 'B', 'Los pesos'],
            'string weight' => [['A' => ['B' => '1'], 'B' => []], 'A', 'B', 'Los pesos'],
            'boolean weight' => [['A' => ['B' => true], 'B' => []], 'A', 'B', 'Los pesos'],
        ];
    }

    public function test_rejects_accumulated_cost_overflow(): void
    {
        $graph = ['A' => ['B' => PHP_FLOAT_MAX], 'B' => ['C' => PHP_FLOAT_MAX], 'C' => []];
        $this->expectException(OverflowException::class);

        (new DijkstraService)->findShortestPath($graph, 'A', 'C');
    }
}
