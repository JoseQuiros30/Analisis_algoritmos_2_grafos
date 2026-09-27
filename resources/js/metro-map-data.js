const lineA = ['niquia', 'bello', 'acevedo', 'universidad', 'hospital', 'prado', 'parque-berrio', 'san-antonio', 'alpujarra', 'industriales', 'poblado', 'aguacatala', 'ayura', 'envigado', 'itagui'];
const lineB = ['san-javier', 'santa-lucia', 'floresta', 'estadio', 'suramericana', 'cisneros'];

export function routeTimeline(route) {
    if (!route?.found) return [];
    let elapsed = 0;
    return route.stations.map((station, index) => {
        const leg = index > 0 ? route.legs[index - 1] : null;
        elapsed += leg?.costs.total ?? 0;
        return { ...station, elapsed, leg };
    });
}

export function buildMapElements(data, route) {
    const timeline = routeTimeline(route);
    const stops = new Map(timeline.map((stop) => [String(stop.id), stop]));
    const routeLegs = new Map((route?.found ? route.legs : []).map((leg) => [Number(leg.connection_id), leg]));
    const nodes = data.stations.map((station, index) => {
        const a = lineA.indexOf(station.code);
        const b = lineB.indexOf(station.code);
        const stop = stops.get(String(station.id));
        return {
            group: 'nodes',
            data: { id: `s${station.id}`, stationId: station.id, name: station.name,
                label: stop ? `${station.name}\n${stop.elapsed} min` : station.name,
                color: b >= 0 ? '#ef790c' : a >= 0 ? '#1867ae' : '#64748b' },
            position: a >= 0 ? { x: 460, y: 60 + a * 62 }
                : b >= 0 ? { x: 40 + b * 65, y: 494 }
                    : { x: 740 + (index % 3) * 170, y: 70 + Math.floor(index / 3) * 75 },
            classes: [b >= 0 ? 'branch' : '', station.code === 'san-antonio' ? 'interchange' : '', stop ? 'on-route' : '',
                stop && stop === timeline[0] ? 'origin' : '', stop && stop === timeline.at(-1) ? 'destination' : ''].join(' '),
        };
    });
    const nodeIds = new Set(data.stations.map((station) => Number(station.id)));
    const grouped = new Map();
    for (const connection of data.connections) {
        if (!nodeIds.has(Number(connection.origin_station_id)) || !nodeIds.has(Number(connection.destination_station_id))) continue;
        const pair = [Number(connection.origin_station_id), Number(connection.destination_station_id)].sort((a, b) => a - b);
        const key = JSON.stringify([...pair, connection.line]);
        if (!grouped.has(key)) grouped.set(key, []);
        grouped.get(key).push(connection);
    }
    const edges = [...grouped.values()].map((connections) => {
        const selected = connections.find((connection) => routeLegs.has(Number(connection.id)));
        const connection = selected ?? connections[0];
        const leg = selected ? routeLegs.get(Number(selected.id)) : null;
        const hasReverse = connections.some((candidate) => candidate.origin_station_id === connection.destination_station_id && candidate.destination_station_id === connection.origin_station_id);
        return {
            group: 'edges',
            data: { id: `e${connections[0].id}`, source: `s${connection.origin_station_id}`, target: `s${connection.destination_station_id}`,
                connections, selectedId: selected?.id ?? null, line: connection.line,
                color: connection.line === 'A' ? '#1867ae' : connection.line === 'B' ? '#ef790c' : '#64748b',
                label: leg ? `${leg.costs.total} min` : '', arrow: leg || !hasReverse ? 'triangle' : 'none' },
            classes: [leg ? 'on-route' : '', connection.line === 'B' ? 'branch-edge' : ''].join(' '),
        };
    });
    return [...nodes, ...edges];
}

export function formatLegDetails(leg) {
    const costs = leg.costs;
    return `${leg.origin} → ${leg.destination} · Línea ${leg.line} · ${costs.total} min = ${costs.base_time} base + ${costs.weather_penalty} lluvia + ${costs.peak_hour_penalty} hora pico + ${costs.congestion_penalty} congestión + ${costs.transfer_penalty} transbordo.`;
}
