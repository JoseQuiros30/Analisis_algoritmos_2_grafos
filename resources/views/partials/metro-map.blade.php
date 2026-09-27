<section id="metro-map-panel" class="metro-map-panel lg:row-span-2" aria-labelledby="network-title">
    <div class="map-heading">
        <div><p class="map-eyebrow">02 / Red esquemática</p><h2 id="network-title">Tu recorrido en el mapa</h2></div>
        <span class="map-north" aria-label="Norte arriba">↑ N</span>
    </div>
    <div class="map-legend" aria-label="Leyenda">
        <span><i class="line-dot line-a" aria-hidden="true"></i>A · Niquía — Itagüí</span>
        <span><i class="line-dot line-b" aria-hidden="true"></i>B · San Antonio — San Javier</span>
    </div>
    <div class="metro-map-stage">
        <div class="map-river" aria-hidden="true"><span>Río Medellín</span></div>
        <div id="metro-map" role="img" aria-label="Mapa esquemático de estaciones y conexiones. El recorrido también se presenta como lista debajo del mapa."></div>
        <div class="map-tools" hidden>
            <button type="button" data-map-action="zoom-in" aria-label="Acercar mapa">+</button>
            <button type="button" data-map-action="zoom-out" aria-label="Alejar mapa">−</button>
            <button type="button" data-map-action="fit" aria-label="Ajustar mapa completo">⌖</button>
        </div>
        <p id="map-empty" class="map-empty" hidden>No hay estaciones disponibles.</p>
        <noscript><p class="map-empty">Activa JavaScript para explorar el mapa. El cálculo y la lista de estaciones del recorrido siguen disponibles en el formulario.</p></noscript>
    </div>
    <div class="map-playback" hidden>
        <div class="map-progress-heading"><strong id="map-trip-total"></strong><span id="map-trip-scenario"></span></div>
        <div class="map-playback-controls">
            <button type="button" id="map-play" class="map-primary">Reproducir</button>
            <button type="button" id="map-restart" class="map-secondary">Reiniciar</button>
            <label for="map-progress" class="sr-only">Avance por estaciones</label>
            <input type="range" id="map-progress" min="0" max="0" value="0" step="1">
        </div>
        <p id="map-progress-status" role="status" aria-live="polite"></p>
        <p class="map-small">Animación ilustrativa; los minutos mostrados son los costos simulados, no la duración real de la animación.</p>
    </div>
    <p id="map-status" class="map-status" role="status">Calcula una ruta para resaltarla y ver sus tiempos.</p>
    <p id="map-detail" class="map-detail" role="status">Arrastra para mover el mapa; usa +/− o dos dedos para ampliar. Toca una estación o conexión para consultar sus tiempos.</p>
    <details class="map-station-list"><summary>Ver catálogo de estaciones ({{ $stations->count() }})</summary><ul>@foreach($stations as $station)<li>{{ $station->name }}</li>@endforeach</ul></details>
    <p class="map-disclaimer">Esquema del catálogo académico de 21 estaciones, no un mapa oficial ni a escala. Las líneas y estaciones omitidas de la red real no se representan.</p>
    <script type="application/json" id="metro-map-data">{!! json_encode($mapData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
</section>
