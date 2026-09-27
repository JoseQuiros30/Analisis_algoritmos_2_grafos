# MetroRoute Medellín

Proyecto académico de Jose y Anderson para Análisis de Algoritmos. Estudiaremos rutas entre estaciones del Metro de Medellín mediante grafos, pesos dinámicos y una implementación manual de Dijkstra.

Los tiempos y penalizaciones serán datos simulados para fines académicos. La aplicación no representa un servicio oficial del Metro.

## Estado actual

Primera entrega de Jose: base de Laravel, SQLite, zona horaria `America/Bogota`, idioma español y bienvenida en `/` (ruta `home`). La comprobación de salud de Laravel está en `/up`.

Ya existe el catálogo de 21 estaciones representativas. Las conexiones del grafo, el calculador, los pesos dinámicos y Dijkstra todavía no están implementados.

## Instalación local

Requisitos: PHP 8.3 o superior, Composer y extensión `pdo_sqlite`. Node.js y npm son necesarios cuando se utilicen los recursos de Vite; la bienvenida actual no requiere compilación.

Desde la raíz de una copia nueva del repositorio:

```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan db:seed
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

La página inicial debe mostrar MetroRoute Medellín y el aviso de proyecto en desarrollo. Aún no permite calcular rutas.

## Estaciones

`Station` representa un vértice del futuro grafo. La tabla `stations` contiene `id`, `code` (único, hasta 50 caracteres), `name` (hasta 100 caracteres) y marcas de tiempo. Los códigos son identificadores internos del proyecto, no códigos oficiales del Metro. El catálogo toma las 21 estaciones propuestas en el alcance académico; no representa toda la red.

`StationSeeder` busca por código y actualiza el nombre sin duplicar estaciones ni cambiar sus IDs. Conserva estaciones adicionales. Puedes ejecutarlo de nuevo con `php artisan db:seed --class=StationSeeder`. El seeder general ahora carga estaciones y no crea el usuario de ejemplo de Laravel.

Las líneas y los transbordos se definirán al implementar conexiones: una estación compartida, como San Antonio, conserva un único registro. Todavía no hay relaciones hacia conexiones ni cálculo de rutas.

Para revisar la tabla y ejecutar sus pruebas:

```sh
php artisan db:table stations
php artisan test --compact tests/Feature/StationTest.php tests/Feature/StationSeederTest.php
```

Las pruebas usan SQLite en memoria y no modifican tu base local.

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

Siguiente funcionalidad de Jose: modelo y migración de conexiones con sus relaciones a estaciones, en `dev/jose`.

## Herramientas de desarrollo

Laravel Boost está instalado como dependencia de desarrollo. Sus guías están en `AGENTS.md` y su configuración compartida en `boost.json`. Para configurar la integración con tu agente local:

```sh
php artisan boost:install
```

Para futuros recursos JavaScript y CSS:

```sh
npm install
npm run dev
```
