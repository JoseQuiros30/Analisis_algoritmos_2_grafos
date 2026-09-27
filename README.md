# MetroRoute Medellín

Proyecto académico de Jose y Anderson para Análisis de Algoritmos. Estudiaremos rutas entre estaciones del Metro de Medellín mediante grafos, pesos dinámicos y una implementación manual de Dijkstra.

Los tiempos y penalizaciones serán datos simulados para fines académicos. La aplicación no representa un servicio oficial del Metro.

## Estado actual

Primera entrega de Jose: base de Laravel, SQLite, zona horaria `America/Bogota`, idioma español y bienvenida en `/` (ruta `home`). La comprobación de salud de Laravel está en `/up`.

El grafo, el calculador, los pesos dinámicos y Dijkstra todavía no están implementados.

## Instalación local

Requisitos: PHP 8.3 o superior, Composer y extensión `pdo_sqlite`. Node.js y npm son necesarios cuando se utilicen los recursos de Vite; la bienvenida actual no requiere compilación.

Desde la raíz de una copia nueva del repositorio:

```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
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

## Trabajo colaborativo

Antes de cada funcionalidad, con el árbol de trabajo limpio:

```sh
git checkout main
git pull origin main
git checkout -b feature/nombre-de-la-funcionalidad
```

Una rama por funcionalidad y por integrante; commits pequeños con Conventional Commits y Pull Request hacia `main`. Revisa las pruebas y los cambios del compañero antes de integrar. Los commits conservan la identidad Git del integrante que realizó el trabajo.

- **Jose:** configuración, estaciones y conexiones, pesos dinámicos y clima, interfaz, comparación de escenarios, resultados y sus pruebas.
- **Anderson:** Dijkstra, registro de ejecución, detección de hora pico y congestión, visualización con Cytoscape, modo académico, incidentes y sus pruebas.

Dijkstra consumirá el servicio de costos de Jose. La detección de hora pico y congestión pertenece a Anderson; Jose integrará sus penalizaciones sin duplicar esa lógica.

Siguiente funcionalidad de Jose: modelo y migración de estaciones, en una rama nueva después de integrar esta entrega.

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
