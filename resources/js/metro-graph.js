import cytoscape from 'cytoscape';
import { buildMapElements, routeTimeline, formatLegDetails } from './metro-map-data';

const container = document.querySelector('#metro-map');
const payload = document.querySelector('#metro-map-data');

if (container && payload) {
    const data = JSON.parse(payload.textContent);
    const route = data.routeResults?.scenarios[data.routeResults.selected];
    const timeline = routeTimeline(route);
    const status = document.querySelector('#map-status');
    const detail = document.querySelector('#map-detail');
    const playback = document.querySelector('.map-playback');
    const play = document.querySelector('#map-play');
    const slider = document.querySelector('#map-progress');
    const progress = document.querySelector('#map-progress-status');
    let timer = null;
    let step = 0;
    let stale = false;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const cy = cytoscape({
        container,
        elements: buildMapElements(data, route),
        layout: { name: 'preset', fit: true, padding: 40 },
        minZoom: 0.15, maxZoom: 3,
        userZoomingEnabled: true, userPanningEnabled: true,
        autoungrabify: true, boxSelectionEnabled: false,
        style: [
            { selector: 'node', style: { width: 13, height: 13, 'background-color': '#fff', 'border-width': 3, 'border-color': 'data(color)',
                label: 'data(label)', 'font-size': 16, 'font-family': 'system-ui, sans-serif', 'font-weight': 500, color: '#243b4b',
                'text-halign': 'right', 'text-valign': 'center', 'text-margin-x': 12, 'text-wrap': 'wrap', 'text-background-color': '#fff', 'text-background-opacity': 0.85, 'text-background-padding': '2px' } },
            { selector: 'node.branch', style: { 'text-rotation': -0.7, 'text-halign': 'right', 'text-valign': 'top', 'text-margin-y': -16, 'text-margin-x': 0 } },
            { selector: 'node.interchange', style: { width: 21, height: 21, 'border-width': 4, 'border-color': '#34495e', 'background-color': '#fff' } },
            { selector: 'edge', style: { width: 5, 'line-color': 'data(color)', 'curve-style': 'straight', 'target-arrow-shape': 'data(arrow)', 'target-arrow-color': 'data(color)', 'arrow-scale': 0.65,
                label: 'data(label)', 'font-size': 13, color: '#334155', 'text-background-opacity': 1, 'text-background-color': '#fff', 'text-background-padding': '3px', 'text-margin-x': -30, 'text-wrap': 'wrap' } },
            { selector: 'edge.branch-edge', style: { 'text-margin-x': 0, 'text-margin-y': 25 } },
            { selector: 'edge.demo-edge', style: { 'curve-style': 'unbundled-bezier', 'control-point-distances': -150, 'control-point-weights': 0.5, 'line-style': 'dashed' } },
            { selector: 'edge.directed-edge', style: { 'curve-style': 'unbundled-bezier', 'control-point-distances': 35, 'control-point-weights': 0.5 } },
            { selector: 'edge.closed-edge', style: { 'line-style': 'dashed', 'line-color': '#dc2626', 'target-arrow-color': '#dc2626', color: '#b91c1c', opacity: 1 } },
            { selector: '.dimmed', style: { opacity: 0.4, 'text-opacity': 0.85 } },
            { selector: 'edge.on-route', style: { width: 8, 'z-index': 5 } },
            { selector: 'node.on-route', style: { 'font-weight': 700, 'z-index': 10 } },
            { selector: 'node.origin, node.destination', style: { width: 22, height: 22, 'border-width': 4, 'border-color': '#047857' } },
            { selector: 'edge.travelled', style: { 'line-color': '#059669', 'target-arrow-color': '#059669' } },
            { selector: 'node.current', style: { width: 26, height: 26, 'background-color': '#a7f3d0', 'border-color': '#047857', 'border-width': 5 } },
        ],
    });
    const fit = () => { cy.resize(); if (cy.nodes().length) cy.fit(cy.elements(), 40); };
    document.querySelector('.map-tools').hidden = !data.stations.length;
    document.querySelector('#map-empty').hidden = !!data.stations.length;
    if (route?.found) {
        cy.elements().not('.on-route, .closed-edge').addClass('dimmed');
        playback.hidden = false;
        slider.max = timeline.length - 1;
        document.querySelector('#map-trip-total').textContent = `${route.costs.total} min · ${timeline.length} estaciones · ${route.transfer_count} ${route.transfer_count === 1 ? 'transbordo' : 'transbordos'}`;
        document.querySelector('#map-trip-scenario').textContent = route.label;
        status.textContent = 'Ruta resaltada. Las etiquetas indican minutos por tramo y acumulados al llegar a cada estación.';
    } else if (route) {
        status.textContent = 'No existe una ruta disponible entre las estaciones seleccionadas. Se muestra la red completa.';
    }

    function pause() {
        clearInterval(timer);
        timer = null;
        play.textContent = reducedMotion.matches ? 'Avanzar estación' : 'Reproducir';
        play.setAttribute('aria-pressed', 'false');
    }
    function showStep(index) {
        if (stale || !timeline.length) return;
        step = Math.max(0, Math.min(Number(index), timeline.length - 1));
        slider.value = step;
        cy.batch(() => {
            cy.elements().removeClass('current travelled');
            cy.getElementById(`s${timeline[step].id}`).addClass('current');
            const traversed = new Set(route.legs.slice(0, step).map((leg) => Number(leg.connection_id)));
            cy.edges().forEach((edge) => { if (traversed.has(Number(edge.data('selectedId')))) edge.addClass('travelled'); });
        });
        const stop = timeline[step];
        progress.textContent = `${step === timeline.length - 1 ? 'Llegada: ' : ''}${stop.name} · ${stop.elapsed} de ${route.costs.total} min${stop.leg ? ` · último tramo ${stop.leg.costs.total} min (línea ${stop.leg.line})` : ' · inicio'}`;
        slider.setAttribute('aria-valuetext', `${stop.name}, ${stop.elapsed} minutos acumulados`);
    }
    pause();
    showStep(0);
    play.addEventListener('click', () => {
        if (stale) return;
        if (reducedMotion.matches) { showStep(step >= timeline.length - 1 ? 0 : step + 1); return; }
        if (timer) { pause(); return; }
        if (step >= timeline.length - 1) showStep(0);
        play.textContent = 'Pausar';
        play.setAttribute('aria-pressed', 'true');
        timer = setInterval(() => { showStep(step + 1); if (step >= timeline.length - 1) pause(); }, 1100);
    });
    document.querySelector('#map-restart').addEventListener('click', () => { pause(); showStep(0); });
    slider.addEventListener('input', () => { pause(); showStep(slider.value); });
    document.querySelectorAll('[data-map-action]').forEach((button) => button.addEventListener('click', () => {
        const action = button.dataset.mapAction;
        if (action === 'fit') return fit();
        cy.zoom({ level: cy.zoom() * (action === 'zoom-in' ? 1.25 : 0.8), renderedPosition: { x: cy.width() / 2, y: cy.height() / 2 } });
    }));
    cy.on('tap', 'node', (event) => {
        const stop = !stale && timeline.find((item) => Number(item.id) === Number(event.target.data('stationId')));
        detail.textContent = stop ? `${stop.name} · ${stop.elapsed} min acumulados desde el origen.` : event.target.data('name');
    });
    cy.on('tap', 'edge', (event) => {
        const edge = event.target;
        const leg = !stale && route?.legs.find((item) => Number(item.connection_id) === Number(edge.data('selectedId')));
        if (leg) {
            detail.textContent = formatLegDetails(leg);
        } else {
            const names = new Map(data.stations.map((station) => [Number(station.id), station.name]));
            detail.textContent = edge.data('connections').map((connection) => `${names.get(Number(connection.origin_station_id))} → ${names.get(Number(connection.destination_station_id))}: ${connection.closed ? 'CERRADO · ' : ''}${connection.base_time} min base (línea ${connection.line})`).join(' / ');
        }
    });
    document.addEventListener('metro:selection-changed', () => {
        stale = true;
        pause();
        playback.hidden = true;
        cy.batch(() => {
            cy.elements().removeClass('dimmed on-route current travelled origin destination');
            cy.nodes().forEach((node) => node.data('label', node.data('name')));
            cy.edges().forEach((edge) => { edge.data('label', edge.hasClass('closed-edge') ? 'Cerrado (cálculo anterior)' : ''); edge.data('arrow', edge.data('connections').length > 1 ? 'none' : 'triangle'); });
        });
        status.textContent = 'Cambiaste las condiciones. Calcula de nuevo para actualizar el mapa y los tiempos.';
        detail.textContent = 'El recorrido anterior se ha retirado del mapa.';
    });
    reducedMotion.addEventListener('change', pause);
    document.addEventListener('visibilitychange', () => { if (document.hidden) pause(); });
    window.addEventListener('pagehide', pause);
    new ResizeObserver(fit).observe(container);
}
