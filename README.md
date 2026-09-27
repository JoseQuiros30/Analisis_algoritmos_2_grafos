# MetroRoute Medellín

Proyecto académico de Jose y Anderson para Análisis de Algoritmos. Estudiaremos rutas entre estaciones del Metro de Medellín mediante grafos, pesos dinámicos y una implementación manual de Dijkstra.

Los tiempos y penalizaciones serán datos simulados para fines académicos. La aplicación no representa un servicio oficial del Metro.

## Estado actual

Primera entrega de Jose: base de Laravel, SQLite, zona horaria `America/Bogota`, idioma español y bienvenida en `/` (ruta `home`). La comprobación de salud de Laravel está en `/up`.

Ya existe el catálogo de 21 estaciones y 20 tramos simplificados (40 conexiones dirigidas). El servicio de pesos dinámicos ya calcula costos por conexión y `/escenarios` compara los cuatro escenarios predefinidos. La interfaz permite preparar y validar un recorrido en `/planificador`; Dijkstra todavía no está integrado.

## Instalación local

Requisitos: PHP 8.3 o superior, Composer y extensión `pdo_sqlite`. El planificador requiere Node.js compatible con Vite 8 (20.19+ o 22.12+) y npm para compilar sus estilos y JavaScript. La bienvenida permanece accesible sin Vite.

Desde la raíz de una copia nueva del repositorio:

```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan db:seed
npm ci
npm run build
php artisan serve
```

Abre la dirección que muestre `php artisan serve`. Si ya tienes `.env`, conserva sus valores y ajusta `APP_NAME="MetroRoute Medellín"`, `APP_LOCALE=es` y `DB_CONNECTION=sqlite` sin sobrescribir tu clave.

SQLite utiliza `database/database.sqlite` cuando no se define `DB_DATABASE`. Las claves foráneas están habilitadas. Cada integrante tiene su propia base local; `.env` y los archivos SQLite se excluyen de Git. Las migraciones existentes crean las tablas base para usuarios, sesiones, caché y trabajos.

No hace falta iniciar MySQL en MAMP. Ejecuta `php artisan migrate` al recibir nuevas migraciones; evita `migrate:fresh` si quieres conservar tus datos.

## Verificación

```sh
php artisan migrate:status
php artisan route:list --except-vendor
php artisan test --compact
```

La página inicial muestra MetroRoute Medellín y el enlace «Abrir el planificador». El planificador valida la selección pero todavía no calcula rutas.

## Estaciones

`Station` representa un vértice del futuro grafo. La tabla `stations` contiene `id`, `code` (único, hasta 50 caracteres), `name` (hasta 100 caracteres) y marcas de tiempo. Los códigos son identificadores internos del proyecto, no códigos oficiales del Metro. El catálogo toma las 21 estaciones propuestas en el alcance académico; no representa toda la red.

`StationSeeder` busca por código y actualiza el nombre sin duplicar estaciones ni cambiar sus IDs. Conserva estaciones adicionales. Puedes ejecutarlo de nuevo con `php artisan db:seed --class=StationSeeder`. El seeder general ahora carga estaciones y no crea el usuario de ejemplo de Laravel.

Las líneas y los transbordos se definirán al implementar conexiones: una estación compartida, como San Antonio, conserva un único registro. Cada estación expone `outgoingConnections()` e `incomingConnections()`. Todavía no hay cálculo de rutas.

Para revisar la tabla y ejecutar sus pruebas:

```sh
php artisan db:table stations
php artisan test --compact tests/Feature/StationTest.php tests/Feature/StationSeederTest.php
```

Las pruebas usan SQLite en memoria y no modifican tu base local.

## Conexiones

`Connection` representa una arista dirigida. Sus campos son `origin_station_id`, `destination_station_id`, `line`, `base_time`, `weather_penalty`, `peak_hour_penalty`, `congestion_penalty` y `transfer_penalty`, además del ID y marcas de tiempo. `originStation()` y `destinationStation()` permiten acceder a sus extremos.

Cada tramo bidireccional se guarda como dos registros independientes. La combinación origen, destino y línea es única; se permiten líneas distintas entre los mismos extremos. Las claves foráneas impiden referencias inexistentes y borrar estaciones que tengan conexiones.

Los tiempos se expresan en minutos enteros. Al guardar mediante el modelo se valida tiempo base mayor que cero, penalizaciones no negativas, línea obligatoria y extremos distintos. Estas validaciones se ejecutan en eventos de Eloquent: las escrituras masivas con query builder o SQL directo las omiten; usa `create`, `save`, `update` sobre una instancia o `updateOrCreate`. Las restricciones de unicidad y claves foráneas sí pertenecen a SQLite.

El seeder carga 14 tramos de la línea A y 6 de la B, todos en ambos sentidos. Conecta las estaciones seleccionadas consecutivamente: algunos tramos agrupan estaciones omitidas. **Es una red simplificada, no un mapa completo de conexiones reales.** Todos sus costos son simulados:

| Campo | Línea A | Línea B |
| --- | --- | --- |
| Tiempo base | 4 | 3 |
| Penalización por lluvia | 1 | 1 |
| Penalización por hora pico | 2 | 2 |
| Penalización por congestión | 1 | 1 |
| Penalización por transbordo | 0 | 0 |

Estos valores son parámetros de `RouteCostService`, no penalizaciones que se sumen siempre. La detección de hora pico y congestión sigue a cargo de Anderson. El transbordo por cambio de línea requiere conocer la línea anterior; aún no se calcula y no debe cobrarse en cada tramo de una línea. El campo `transfer_penalty` queda disponible para conexiones que explícitamente representen un transbordo.

La topología inicial es un árbol bidireccional: cambiar sus pesos altera el tiempo, pero no genera otro camino simple. Para la demostración académica de rutas alternativas habrá que acordar y añadir conexiones adicionales claramente identificadas como simuladas.

```sh
php artisan migrate
php artisan db:seed
php artisan db:table connections
php artisan test --compact tests/Feature/ConnectionTest.php tests/Feature/ConnectionSeederTest.php
```

El seeder general carga estaciones antes de conexiones. `ConnectionSeeder` falla con un mensaje claro si faltan estaciones. Puede repetirse sin duplicar conexiones ni cambiar IDs; restablece los costos del catálogo y conserva conexiones ajenas a él. Para conservar cambios manuales en costos, no repitas el seeder sobre esos datos.

## Pesos dinámicos

`app/Services/RouteCostService.php` calcula el peso de **una conexión**, sin consultar la base de datos, cambiar sus atributos ni persistir el resultado. Todos los costos son simulados y están expresados en minutos enteros.

```php
use App\Services\RouteCostService;

// $connection es una instancia de Connection cargada por el llamador.
$breakdown = (new RouteCostService)->calculate(
    connection: $connection,
    weather: RouteCostService::WEATHER_RAIN,
    isPeakHour: true,
    isCongested: true,
    isTransfer: false,
);
$weight = $breakdown['total']; // Peso que utilizará Dijkstra.
```

Contrato: `calculate(Connection $connection, string $weather = 'normal', bool $isPeakHour = false, bool $isCongested = false, bool $isTransfer = false): array`. Laravel también puede inyectar `RouteCostService` directamente en el constructor de otro servicio.

El resultado siempre contiene `base_time`, `weather_penalty`, `peak_hour_penalty`, `congestion_penalty`, `transfer_penalty` y `total`. Las penalizaciones inactivas se devuelven como cero. La suma es:

**Total = tiempo base + clima aplicado + hora pico aplicada + congestión aplicada + transbordo aplicado.**

- Clima: `normal` no suma penalización; `rain` suma `weather_penalty` de la conexión. Es una selección de simulación, sin API meteorológica.
- Hora pico: `isPeakHour` activa `peak_hour_penalty`. La detección a partir de una hora corresponde a Anderson.
- Congestión: `isCongested` activa `congestion_penalty`. La clasificación de niveles corresponde a Anderson; este contrato inicial solo recibe si la penalización está activa.
- Transbordo: `isTransfer` activa `transfer_penalty` de la conexión. No detecta cambios de línea ni agrega una tarifa global; el catálogo actual tiene esta penalización en cero. Al integrar costos dependientes de la línea anterior, Dijkstra deberá representar ese estado o utilizar aristas explícitas de transbordo para mantener la corrección.

Ejemplos para una conexión con tiempo base 4, lluvia 1, hora pico 2 y congestión 1, sin transbordo:

| Condiciones | Tiempo total |
| --- | --- |
| Normal | 4 |
| Lluvia | 5 |
| Hora pico | 6 |
| Lluvia + hora pico | 7 |
| Lluvia + hora pico + congestión | 8 |

El servicio rechaza climas desconocidos y costos inválidos con `InvalidArgumentException`, incluso si una penalización inválida está inactiva. Revisa los valores sin convertirlos primero a los casts de Eloquent para no ocultar fracciones o datos corruptos. Exige tiempo base positivo y penalizaciones no negativas; también rechaza desbordamiento del total. Una capa HTTP futura deberá validar los controles y convertirlos a los tipos de este contrato.

No se decide la ruta óptima ni se ignoran incidentes aquí. Anderson gestionará conexiones bloqueadas en la construcción del grafo o en Dijkstra. Los escenarios de la tabla están cubiertos por pruebas del servicio. La comparación visual por conexión está disponible en `/escenarios`; comparar rutas completas sigue pendiente de Dijkstra.

```sh
php artisan test --compact tests/Feature/RouteCostServiceTest.php
```

## Interfaz del planificador

Desde la bienvenida, abre **Abrir el planificador** (`GET /planificador`, ruta `routes.index`). Los selectores consultan las estaciones reales de SQLite. El formulario incluye origen, destino, hora en Bogotá y clima Normal/Lluvia. El botón de intercambio requiere JavaScript; enviar y validar el formulario funciona también sin él.

`POST /planificador` (`routes.prepare`) valida los datos mediante `PlanRouteRequest`: estaciones existentes y distintas, hora HH:MM y clima permitido. Conserva las selecciones tras un error y devuelve mensajes en español. Una selección correcta aparece en el resumen, sin guardar un viaje ni calcular tiempos. El resumen es temporal y se muestra tras el envío; JavaScript lo oculta si cambias las condiciones para evitar mostrar datos desactualizados.

«Calcular ruta» permanece deshabilitado hasta integrar Dijkstra. El área central muestra el catálogo alfabético y reserva el espacio para el grafo de Anderson; no simula una ruta ni dibuja conexiones inventadas. Con menos de dos estaciones se deshabilita la preparación.

Verificación manual:

1. Ejecuta `npm run build` y `php artisan serve`; entra por el enlace de la bienvenida.
2. Selecciona Niquía y San Javier, hora 07:30 y Lluvia. Pulsa «Preparar recorrido» y revisa el resumen.
3. Cambia un control: el resumen anterior debe ocultarse. Intercambia las estaciones y vuelve a enviar.
4. Selecciona el mismo origen y destino: debe aparecer un error en español conservando los demás controles.
5. Revisa el formulario con teclado y en una ventana estrecha. Sin JavaScript, el envío sigue funcionando.

```sh
php artisan test --compact tests/Feature/RoutePlannerTest.php
```

## Comparación de escenarios por conexión

Entra al planificador y pulsa **Comparar escenarios**. La página `GET /escenarios` (`scenarios.index`) permite escoger una conexión dirigida, incluida su línea, y destacar uno de cuatro escenarios. Pulsa **Comparar escenarios** para aplicar la selección. La tabla siempre compara los cuatro sobre esa misma conexión, y la tarjeta destaca el escenario elegido. Los resultados corresponden al último envío, no a cambios sin confirmar en los controles.

`ScenarioComparisonService` centraliza los escenarios y utiliza `RouteCostService` sin duplicar su fórmula. El método `compare(Connection $connection)` devuelve un array indexado por `normal`, `rain`, `peak_hour` y `rain_peak_hour`. Cada entrada incluye `label`, `weather`, `is_peak_hour`, `costs` (el desglose del servicio de costos) y `difference` (minutos adicionales respecto a Normal).

- **Normal:** sin lluvia ni hora pico.
- **Lluvia:** activa solamente lluvia.
- **Hora pico:** activa solamente la penalización de hora pico.
- **Lluvia + hora pico:** activa ambas penalizaciones.

Congestión y transbordo se mantienen desactivados para aislar estas dos variables. Hora pico es un interruptor del escenario académico: esta pantalla no interpreta horarios ni sustituye la detección de Anderson. Los escenarios no modifican la base de datos ni las condiciones seleccionadas en el planificador.

La selección predeterminada es la primera conexión ordenada por línea e ID y el escenario Normal. Sin conexiones aparece un estado vacío. `CompareScenariosRequest` rechaza IDs inexistentes y escenarios desconocidos; redirige a la página limpia para evitar ciclos con parámetros inválidos. Cada sentido se compara con sus propios costos. Las etiquetas de estaciones se escapan en Blade.

```php
// $connection es una instancia de Connection previamente cargada.
$comparison = app(\App\Services\ScenarioComparisonService::class)->compare($connection);
$rainMinutes = $comparison['rain']['costs']['total'];
$extraMinutes = $comparison['rain']['difference'];
```

En código de aplicación, inyecta el servicio en el constructor o en el controlador. Este ejemplo también puede utilizarse en Tinker con una conexión cargada. El servicio está listo para reutilizar sus escenarios cuando se integre Dijkstra, pero esta comparación no calcula ni elige una ruta completa.

Prueba manual: elige un tramo de la línea A del catálogo sin modificar, selecciona Lluvia + hora pico y envía. Deben aparecer totales 4, 5, 6 y 7 minutos, diferencias 0, 1, 2 y 3, y una tarjeta de 7 minutos. En un tramo de la línea B los totales son 3, 4, 5 y 6. Comprueba también el sentido inverso y el formulario sin JavaScript.

```sh
php artisan test --compact tests/Feature/ScenarioComparisonTest.php tests/Feature/ScenarioPageTest.php
npm run build
```

## Entregas pendientes de Jose

Con la comparación **por conexión** terminada quedan **3 entregas estimadas**:

1. **Resultados e integración de escenarios por ruta:** tiempo total, estaciones, transbordos, desglose, timeline y comparación de rutas completas. Requiere la salida real de Dijkstra de Anderson.
2. **Integración y pruebas finales:** conectar detección de hora pico/congestión; acordar conexiones alternativas simuladas y representación de transbordos para demostrar cambios de ruta. Depende de ambos integrantes.
3. **Documentación académica final:** consolidar problema, arquitectura, datos, fórmula de pesos, clima y hora pico, ejemplos y guía de demostración con los comportamientos finales. El README ya cubre la implementación actual de Jose.

La comparación de escenarios por ruta sigue pendiente dentro de la primera entrega; la pantalla por conexión no la reemplaza. Esta división estima entregas, no commits ni duración. Jose puede revisar su documentación mientras Anderson prepara el algoritmo; el cierre funcional requiere esa integración.

## Trabajo colaborativo

Usamos ramas fijas: `dev/jose` y `dev/anderson`. `main` recibe entregas revisadas mediante Pull Requests. Antes de cada entrega, guarda tus cambios en commits y sincroniza tu rama (ejemplo de Jose):

```sh
git checkout main
git pull origin main
git switch dev/jose
git merge main
```

Las ramas se crean una sola vez con `git switch -c dev/jose` o `git switch -c dev/anderson`. En nuevas copias, si la rama ya está publicada, usa `git switch --track origin/dev/jose` (o la de Anderson). Para esta transición, `dev/jose` parte de la rama de configuración inicial y conserva sus commits.

Cada integrante trabaja en su propia rama, con commits pequeños por funcionalidad y Conventional Commits. Abre un Pull Request hacia `main` al terminar cada entrega. Revisa las pruebas y los cambios del compañero antes de integrar. Usa **Create a merge commit** para conservar los commits individuales y facilitar la reutilización de las ramas; conserva también la rama al cerrar el PR. Después, incorpora `main` nuevamente en tu rama. Los commits conservan la identidad Git del integrante que realizó el trabajo.

- **Jose:** configuración, estaciones y conexiones, pesos dinámicos y clima, interfaz, comparación de escenarios, resultados y sus pruebas.
- **Anderson:** Dijkstra, registro de ejecución, detección de hora pico y congestión, visualización con Cytoscape, modo académico, incidentes y sus pruebas.

Dijkstra consumirá el servicio de costos de Jose. La detección de hora pico y congestión pertenece a Anderson; Jose integrará sus penalizaciones sin duplicar esa lógica.

Siguiente funcionalidad de Jose: resultados y comparación de rutas completas cuando esté disponible Dijkstra, en `dev/jose`.

## Herramientas de desarrollo

Laravel Boost está instalado como dependencia de desarrollo. Sus guías están en `AGENTS.md` y su configuración compartida en `boost.json`. Para configurar la integración con tu agente local:

```sh
php artisan boost:install
```

Para desarrollar los recursos JavaScript y CSS:

```sh
npm ci
npm run dev
```
