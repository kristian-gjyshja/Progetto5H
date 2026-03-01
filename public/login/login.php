<?php
require_once __DIR__ . '/../../app/config.php';

if (isset($_SESSION['ruolo'], $_SESSION['email'])) {
    if ($_SESSION['ruolo'] === 'admin') {
        header('Location: ' . url('public/admin/index.php'));
        exit();
    }

    if ($_SESSION['ruolo'] === 'user') {
        header('Location: ' . url('public/customers/index.php'));
        exit();
    }
}

$errorKey = trim((string) ($_GET['error'] ?? ''));
$errorMessages = [
    'missing_fields' => 'Compila tutti i campi richiesti.',
    'invalid_credentials' => 'Credenziali non valide.',
    'invalid_role' => 'Ruolo non valido.',
    'sessione_scaduta' => 'Sessione scaduta. Effettua di nuovo il login.',
];
$errorMessage = $errorMessages[$errorKey] ?? null;
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SubManager Pro</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('css/tailwind-lite.css')) ?>">
  <style>
    body {
      background: linear-gradient(135deg, #6b46c1, #9f7aea, #805ad5);
    }
  </style>
</head>
<body class="flex min-h-screen items-center justify-center">
  <div class="w-96 overflow-hidden rounded-2xl shadow-2xl">
    <div class="bg-gradient-to-r from-purple-700 via-purple-600 to-purple-500 p-6 text-center">
      <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-white text-purple-700 shadow-md">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-8 w-8" fill="currentColor" aria-hidden="true">
          <path d="M2 7a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v1H2V7Zm20 3H2v7a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-7ZM6 15a1 1 0 0 1 0-2h3a1 1 0 1 1 0 2H6Z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">SubManager Pro</h1>
      <h4 class="text-sm text-white">Sistema di gestione abbonamenti</h4>
    </div>

    <div class="space-y-6 bg-white p-8">
      <h2 class="text-xl font-bold text-slate-800">Accedere al sistema</h2>

      <?php if ($errorMessage !== null): ?>
        <div class="rounded bg-red-100 p-3 text-center text-red-700">
          <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= htmlspecialchars(url('public/login/login_action.php')) ?>" class="space-y-6">
        <div class="relative">
          <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
          <span class="pointer-events-none absolute inset-y-0 left-0 top-6 flex items-center pl-3 text-purple-500">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
              <path d="M1.5 8.67v8.58A2.25 2.25 0 0 0 3.75 19.5h16.5a2.25 2.25 0 0 0 2.25-2.25V8.67l-8.69 5.79a3.75 3.75 0 0 1-4.16 0L1.5 8.67Z" />
              <path d="M22.5 6.91v-.16A2.25 2.25 0 0 0 20.25 4.5H3.75A2.25 2.25 0 0 0 1.5 6.75v.16l9 6a2.25 2.25 0 0 0 2.5 0l9-6Z" />
            </svg>
          </span>
          <input
            id="email"
            type="email"
            name="email"
            placeholder="Inserisci email"
            class="w-full rounded-xl border border-purple-300 py-3 pl-11 pr-4 focus:outline-none focus:ring-2 focus:ring-purple-500"
            required
          >
        </div>

        <div class="relative">
          <label for="password" class="mb-1 block text-sm font-semibold text-slate-700">Password</label>
          <span class="pointer-events-none absolute inset-y-0 left-0 top-6 flex items-center pl-3 text-purple-500">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25V9h-.375A2.625 2.625 0 0 0 3.75 11.625v7.125a2.625 2.625 0 0 0 2.625 2.625h11.25a2.625 2.625 0 0 0 2.625-2.625v-7.125A2.625 2.625 0 0 0 17.625 9h-.375V6.75A5.25 5.25 0 0 0 12 1.5Zm3.75 7.5V6.75a3.75 3.75 0 0 0-7.5 0V9h7.5Z" clip-rule="evenodd" />
            </svg>
          </span>
          <input
            id="password"
            type="password"
            name="password"
            placeholder="Inserisci password"
            class="w-full rounded-xl border border-purple-300 py-3 pl-11 pr-4 focus:outline-none focus:ring-2 focus:ring-purple-500"
            required
          >
        </div>

        <button
          type="submit"
          class="w-full rounded-xl bg-purple-600 py-3 text-lg font-semibold text-white transition hover:bg-purple-700"
        >
          Accedi
        </button>

        <a
          href="<?= htmlspecialchars(url('public/index.php')) ?>"
          class="block w-full rounded-xl border border-purple-300 py-3 text-center text-sm font-semibold text-purple-700 transition hover:bg-purple-50"
        >
          Torna alla pagina introduttiva
        </a>
      </form>
    </div>
  </div>
</body>
</html>
