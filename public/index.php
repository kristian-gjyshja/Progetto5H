<?php
require_once __DIR__ . '/../app/config.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SubManager Pro - Chi Siamo</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/tailwind-lite.css')) ?>">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
  <main class="mx-auto max-w-6xl px-6 py-10 md:py-16">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl md:p-12">
      <div class="mb-8 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-indigo-700">
        SubManager Pro
      </div>

      <h1 class="max-w-3xl text-4xl font-extrabold leading-tight text-slate-900 md:text-6xl">
        Gestire gli abbonamenti non deve essere complicato.
      </h1>

      <p class="mt-6 max-w-3xl text-base text-slate-600 md:text-lg">
        Siamo un team che ha creato SubManager Pro per dare a utenti e amministratori uno spazio chiaro dove monitorare servizi, costi, scadenze e stato degli abbonamenti in un unico pannello.
      </p>

      <div class="mt-8 grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
          <h2 class="text-lg font-bold text-indigo-700">Chi siamo</h2>
          <p class="mt-2 text-sm text-slate-600">Un progetto focalizzato su ordine, controllo costi e tracciamento semplice dei rinnovi.</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
          <h2 class="text-lg font-bold text-indigo-700">Cosa facciamo</h2>
          <p class="mt-2 text-sm text-slate-600">Centralizziamo utenti, servizi e abbonamenti con filtri, ricerca, stato e statistiche operative.</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
          <h2 class="text-lg font-bold text-indigo-700">Perche usarlo</h2>
          <p class="mt-2 text-sm text-slate-600">Riduce errori, evita rinnovi dimenticati e rende immediata la visione delle spese ricorrenti.</p>
        </article>
      </div>

      <div class="mt-10">
        <a
          href="<?= htmlspecialchars(url('public/login/logout.php')) ?>"
          class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-3 text-base font-bold text-white transition hover:bg-indigo-500"
        >
          Vai al login
        </a>
      </div>
    </section>
  </main>
</body>
</html>
