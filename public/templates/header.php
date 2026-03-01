<?php require_once __DIR__ . '/head.php'; ?>
<?php
$ruolo = strtolower((string) ($_SESSION['ruolo'] ?? 'admin'));
$dashboardUrl = $ruolo === 'user'
    ? url('public/customers/index.php')
    : url('public/admin/index.php');
$ruoloLabel = $ruolo === 'user' ? 'User' : 'Admin';
$nomeSessione = trim((string) ($_SESSION['nome'] ?? ''));
$iniziale = $nomeSessione !== ''
    ? strtoupper(substr($nomeSessione, 0, 1))
    : 'A';
?>

<header class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center">
  <div class="flex items-center gap-6">
    <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="flex items-center gap-3 text-indigo-600">
      <span class="w-8 h-8 rounded bg-indigo-600 text-white flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor" aria-hidden="true">
          <path d="M2 7a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v1H2V7Zm20 3H2v7a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-7ZM6 15a1 1 0 0 1 0-2h3a1 1 0 1 1 0 2H6Z" />
        </svg>
      </span>
      <span class="font-semibold text-lg">SubManager Pro</span>
    </a>

    <h1 class="text-xl font-semibold text-slate-800">
      <?= $title ?? 'Dashboard' ?>
    </h1>
  </div>

  <div class="flex items-center gap-4">
    <!-- Notifiche -->
    <div class="relative cursor-pointer">
      🔔
      <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-1 rounded-full">
        4
      </span>
    </div>

    <!-- User -->
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center">
        <?= htmlspecialchars($iniziale) ?>
      </div>
      <select class="border rounded px-2 py-1 text-sm">
        <option><?= htmlspecialchars($ruoloLabel) ?></option>
      </select>
    </div>
    <a href="../login/logout.php" class="text-red-500">Logout</a>
  </div>
</header>
