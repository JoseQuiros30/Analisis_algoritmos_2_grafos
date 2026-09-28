# MetroRoute Medellín — Revisión de requisitos

**Fecha:** 27 de septiembre de 2026.  
**Fuente:** consigna inicial “Estamos desarrollando entre dos personas…” proporcionada por Jose. Jose confirmó que no hay requisitos adicionales. No se recibió una rúbrica de puntuación; esta matriz contrasta la consigna, no estima una nota.  
**Estado revisado:** archivos locales de `dev/jose`, incluidos cambios aún sin commit. No certifica el contenido actual de `main` ni una entrega en GitHub.

**Estados:** Implementado = evidencia en código y pruebas; Documentado = material académico preparado; Pendiente = acción de entrega aún no acreditada; Parcial = alcance más limitado que una posible interpretación de la consigna.

| Requisito de la consigna | Estado | Evidencia / alcance |
| --- | --- | --- |
| Laravel, PHP, Blade, JavaScript, CSS, SQLite | Implementado | `composer.json`, `package.json`, vistas y migraciones; configuración de SQLite descrita en README |
| Cytoscape solo para visualizar | Implementado | `resources/js/metro-graph.js`; cálculo en `app/Services/DijkstraService.php` |
| Entre 15 y 25 estaciones | Implementado | `StationSeeder.php`: 21 estaciones; `StationSeederTest.php` |
| Modelos, migraciones, relaciones y seeders | Implementado | `app/Models`, `database/migrations`, `database/seeders`; pruebas de estaciones y conexiones |
| Vértices, aristas y pesos dirigidos | Implementado | 21 estaciones y 40 conexiones; `ConnectionSeederTest.php` |
| Tiempos identificados como simulados | Implementado | Etiquetas del planificador, modo DEMO, README e informe |
| Selección de origen, destino, hora y clima | Implementado | `route-planner.blade.php`, `PlanRouteRequest.php`, `RoutePlannerTest.php` |
| Pesos por base, clima, hora pico y congestión | Implementado | `RouteCostService.php`, `TrafficConditionService.php`, `TrafficCostService.php` y sus pruebas |
| Penalización por transbordo contextual | Implementado | Grafo estación/línea de `RoutePlannerService.php`; `TransferRoutingTest.php` |
| Cuatro escenarios y comparación | Implementado | `/escenarios` compara conexiones; planificador compara cuatro referencias y la selección automática |
| Escenarios rápidos en el formulario | Parcial | Se muestran los cuatro resultados de referencia automáticamente; no hay cuatro botones dedicados para aplicar presets. Sí hay controles de clima y hora. La consigna no detalla la interacción requerida |
| Resumen, estaciones, transbordos y desglose | Implementado | `partials/route-results.blade.php`, pruebas de integración |
| Dijkstra manual: distancias, mínimo, visitados y relajación | Implementado | `DijkstraService.php`; `tests/Unit/Services/DijkstraServiceTest.php` |
| Predecesores y reconstrucción del camino | Implementado | Servicio Dijkstra y reconstrucción de conexiones en el planificador; pruebas unitarias e integración |
| Registro de ejecución | Implementado | `steps`, con capturas posteriores a evaluar vecinos; historial solo de la selección actual |
| Panel académico con nodo, vecinos, distancias, anteriores y visitados | Implementado | `partials/dijkstra-steps.blade.php`; pruebas HTTP y captura `evidencia-dijkstra.png` |
| Mapa con estaciones, líneas, conexiones y ruta resaltada | Implementado | Cytoscape, mapa esquemático y pruebas `MetroMapTest.php` / `metro-map.test.js` |
| Recorrido dinámico con tiempos | Implementado | Animación, controles de reproducción y tiempos acumulados desde las piernas del resultado |
| Cambio de camino cuando varían pesos | Implementado | DEMO de Universidad ↔ San Antonio; `DemoRoutesTest.php`; capturas Normal/Lluvia |
| Bloqueo de conexión, opcional “si hay tiempo” | Implementado | Cierres por consulta y sentido; `ConnectionClosuresTest.php`; exclusión antes de Dijkstra |
| Estado manual “CONGESTIONADA” por conexión, mostrado como ejemplo | Parcial | Hay congestión automática por horario y cierre seleccionable; no un selector persistente NORMAL/CONGESTIONADA/CERRADA por arista. No es necesario para demostrar el bloqueo opcional implementado |
| Ruta inexistente y alternativas ante cierre | Implementado | Respuesta sin ruta, DEMO alternativo y reapertura; pruebas de cierres |
| Pruebas de pesos, clima, escenarios y costos | Implementado | Pruebas Feature de costos, escenarios, tráfico e integración |
| Pruebas de ruta mínima, reconstrucción, bloqueo y ruta inexistente | Implementado | Pruebas Unit de Dijkstra y Feature de rutas, DEMO, transbordos y cierres |
| Documentar complejidad exacta | Documentado | O(V² + E), mínimo lineal sobre estados ampliados; sección de complejidad del informe |
| Problema, arquitectura, datos, clima, tráfico, teoría y reconstrucción | Documentado | `INFORME_ACADEMICO.md` y README |
| Ejemplos, evidencias y exposición | Documentado | `GUIA_EXPOSICION.md` y capturas reales enlazadas en el informe |
| Trabajo en ramas personales | Adaptado por el usuario | Se acordaron `dev/jose` y `dev/anderson` en lugar de rama por función; el trabajo local sigue en `dev/jose` |
| Commits pequeños y significativos, autoría individual | Pendiente para cambios finales | Historial previo disponible; los bloques finales aún no se han guardado en commits. No modificar autores ni juntar todas las funciones en un commit |
| PR hacia main, revisión e integración | Pendiente para cambios finales | No se creó ni fusionó un PR nuevo durante esta preparación documental |
| Presentación final sin fallos visuales | Pendiente de aceptación | Capturas de escritorio y casos concretos respaldan evidencia; no sustituyen revisión completa móvil, teclado ni ensayo del expositor |

## Resultado de la revisión

Los objetivos algorítmicos de la consigna están cubiertos por la implementación local: pesos dinámicos, Dijkstra manual, relajación, reconstrucción, explicación paso a paso, transbordos y cambio real de ruta. También se implementó el bloqueo opcional. La documentación académica se encuentra preparada y acompañada de ejemplos reproducibles.

Se identificaron dos diferencias de interfaz/alcance: ausencia de botones dedicados de escenarios rápidos y ausencia de congestión manual por conexión. Se hacen explícitas para evitar afirmar cumplimiento literal de controles que no existen. Los escenarios se comparan automáticamente, y la congestión se calcula por horario; no se han añadido funcionalidades nuevas durante esta tarea documental.

No procede declarar cerrada la entrega individual mientras los cambios sigan locales: faltan commits, publicación y revisión del PR. Tampoco se puede asignar una calificación: la consigna no contiene ponderaciones ni criterios de nota.

## Evidencia y cierre

- Resultado automatizado de la revisión funcional previa: **158 pruebas PHP, 7 pruebas JavaScript**, compilación correcta. Consultar comandos en el informe para repetirlos.
- Capturas actuales: [DEMO normal](evidencia-demo-normal.png), [lluvia](evidencia-demo-lluvia.png), [panel](evidencia-dijkstra.png), [cierre](evidencia-cierre.png).
- Documento principal: [Informe académico](INFORME_ACADEMICO.md).
- Preparación oral: [Guía de exposición](GUIA_EXPOSICION.md).
- Cierre pendiente: aceptar el resultado visual, registrar commits por función y documentación, subir `dev/jose`, revisar PR e integrar a `main`.
