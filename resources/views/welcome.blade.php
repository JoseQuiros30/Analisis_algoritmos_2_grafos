<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Proyecto académico de grafos y rutas del Metro de Medellín.">
    <title>{{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #12382e; background: #f2f6f3; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { max-width: 960px; margin: 0 auto; padding: clamp(24px, 6vw, 80px) 24px; }
        header { display: flex; align-items: center; gap: 12px; font-weight: 700; }
        .mark { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: #176344; color: white; }
        .intro { margin: 64px 0 40px; }
        .eyebrow { color: #38624e; font-size: .85rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
        h1 { max-width: 720px; margin: 16px 0; font-size: clamp(2.3rem, 6vw, 4rem); line-height: 1.1; letter-spacing: -.04em; }
        p { max-width: 660px; line-height: 1.7; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        article { padding: 24px; border: 1px solid #d6e3da; border-radius: 18px; background: white; }
        h2 { margin: 0; font-size: 1.1rem; }
        article p { margin-bottom: 0; }
        footer { margin-top: 40px; font-size: .9rem; color: #426353; }
    </style>
</head>
<body>
    <main>
        <header><span class="mark" aria-hidden="true">M</span> {{ config('app.name') }}</header>
        <section class="intro" aria-labelledby="welcome-title">
            <p class="eyebrow">Análisis de algoritmos · Medellín</p>
            <h1 id="welcome-title">Cada estación, una conexión por explorar.</h1>
            <p>Estudiamos cómo los grafos y el algoritmo de Dijkstra pueden ayudar a encontrar una ruta entre estaciones del Metro de Medellín.</p>
            <p><strong>Proyecto en desarrollo.</strong> Ya puedes preparar tu recorrido; el cálculo de rutas está pendiente de integración.</p>
            <p><a href="{{ route('routes.index') }}">Abrir el planificador →</a></p>
        </section>
        <section class="cards" aria-label="Conceptos del proyecto">
            <article><h2>Estaciones y conexiones</h2><p>Las estaciones serán los vértices del grafo y sus conexiones, las aristas.</p></article>
            <article><h2>Costos dinámicos</h2><p>Exploraremos cómo el clima, la hora pico y los transbordos afectan el tiempo del recorrido.</p></article>
            <article><h2>Aprender paso a paso</h2><p>El modo académico permitirá seguir las decisiones del algoritmo de Dijkstra.</p></article>
        </section>
        <footer>Proyecto académico de Jose y Anderson. Los tiempos y penalizaciones serán simulados. No es un planificador oficial del Metro.</footer>
    </main>
</body>
</html>
