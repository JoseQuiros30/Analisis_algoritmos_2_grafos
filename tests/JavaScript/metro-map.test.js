import { test } from 'node:test';
import assert from 'node:assert/strict';
import { buildMapElements, routeTimeline, formatLegDetails } from '../../resources/js/metro-map-data.js';

const stations = [{ id: 1, code: 'niquia', name: 'Niquía' }, { id: 2, code: 'bello', name: 'Bello' }, { id: 3, code: 'san-antonio', name: 'San Antonio' }];
const connections = [
    { id: 10, origin_station_id: 1, destination_station_id: 2, line: 'A', base_time: 4 },
    { id: 11, origin_station_id: 2, destination_station_id: 1, line: 'A', base_time: 6 },
    { id: 12, origin_station_id: 1, destination_station_id: 2, line: 'B', base_time: 3 },
];
const route = { found: true, stations: [stations[1], stations[0]], legs: [{ connection_id: 11, costs: { total: 9 } }] };

test('groups reverse edges without losing direction, costs, or parallel lines', () => {
    const elements = buildMapElements({ stations, connections }, route);
    const edges = elements.filter((item) => item.group === 'edges');
    assert.equal(edges.length, 2);
    assert.equal(edges[0].data.source, 's2');
    assert.equal(edges[0].data.target, 's1');
    assert.equal(edges[0].data.selectedId, 11);
    assert.equal(edges[0].data.label, '9 min');
    assert.deepEqual(edges[0].data.connections.map((item) => item.base_time), [4, 6]);
    assert.equal(edges[1].data.label, '');
});

test('uses ordered route legs for accumulated times including the origin at zero', () => {
    assert.deepEqual(routeTimeline(route).map((stop) => [stop.name, stop.elapsed]), [['Bello', 0], ['Niquía', 9]]);
    assert.deepEqual(routeTimeline({ found: false, stations: [], legs: [] }), []);
    assert.deepEqual(routeTimeline(null), []);
});

test('keeps isolated and unknown stations without inventing edges', () => {
    const data = { stations: [...stations, { id: 7, code: 'custom', name: 'Adicional' }], connections: [] };
    const elements = buildMapElements(data);
    assert.equal(elements.length, 4);
    assert.equal(elements[3].data.name, 'Adicional');
    assert.ok(Number.isFinite(elements[3].position.x));
    assert.deepEqual(buildMapElements({ stations: [], connections }), []);
});

test('marks endpoints and accumulated labels using station ids rather than names', () => {
    const nodes = buildMapElements({ stations, connections }, route).filter((item) => item.group === 'nodes');
    assert.match(nodes[1].classes, /origin/);
    assert.match(nodes[0].classes, /destination/);
    assert.equal(nodes[0].data.label, 'Niquía\n9 min');
    assert.equal(nodes[2].data.label, 'San Antonio');
});

test('details include congestion so the displayed breakdown matches the trip total', () => {
    const leg = { origin: 'Universidad', destination: 'Hospital', line: 'A', costs: {
        base_time: 4, weather_penalty: 1, peak_hour_penalty: 2, congestion_penalty: 2, transfer_penalty: 0, total: 9,
    }};
    assert.equal(formatLegDetails(leg), 'Universidad → Hospital · Línea A · 9 min = 4 base + 1 lluvia + 2 hora pico + 2 congestión + 0 transbordo.');
});
