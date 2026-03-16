<?php
require 'check_session.php';
require_once '../../app/dal/UtenteDal.php';
$title = 'Gestione Utenti';

$utenteDal = new UtenteDAL($pdo);
$ricerca = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$queryBase = [];
if ($ricerca !== '') {
    $queryBase['q'] = $ricerca;
}

$redirect = function (array $params = []) use ($queryBase): void {
    $query = http_build_query(array_merge($queryBase, $params));
    $query = $query !== '' ? ('?' . $query) : '';
    header('Location: ' . url('public/admin/utenti.php') . $query);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'aggiungi_utente') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ruoloInput = strtolower(trim((string) ($_POST['ruolo'] ?? 'user')));
    $ruolo = $ruoloInput === 'admin' ? 'admin' : 'user';

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $redirect(['error' => 'campi_non_validi']);
    }

    try {
        if ($utenteDal->aggiungi($nome, $email, $password, $ruolo)) {
            $redirect(['success' => 'aggiunto']);
        }
        $redirect(['error' => 'salvataggio_fallito']);
    } catch (PDOException $e) {
        $redirect(['error' => 'errore_db']);
    }
}

if (isset($_GET['elimina'])) {
    $id = filter_input(INPUT_GET, 'elimina', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($id === false || $id === null) {
        $redirect(['error' => 'id_non_valido']);
    }

    if ((int) $id === (int) ($_SESSION['id'] ?? 0)) {
        $redirect(['error' => 'non_eliminabile']);
    }

    try {
        if ($utenteDal->elimina((int) $id)) {
            $redirect(['success' => 'eliminato']);
        }
        $redirect(['error' => 'non_eliminabile']);
    } catch (PDOException $e) {
        $redirect(['error' => 'errore_db']);
    }
}

$utenti = $utenteDal->getAll();
if ($ricerca !== '') {
    $needle = $ricerca;
    $utenti = array_values(array_filter($utenti, function (array $u) use ($needle): bool {
        $haystack = implode(' ', [
            $u['id'] ?? '',
            $u['nome'] ?? '',
            $u['email'] ?? '',
            $u['ruolo'] ?? '',
            $u['abbonamenti_attivi'] ?? '',
        ]);
        return $haystack !== '' && stripos($haystack, $needle) !== false;
    }));
}

$messaggiSuccesso = [
    'aggiunto' => 'Utente aggiunto correttamente.',
    'eliminato' => 'Utente eliminato correttamente.',
];

$messaggiErrore = [
    'campi_non_validi' => 'Campi non validi. Controlla i dati inseriti (password minimo 6 caratteri).',
    'id_non_valido' => 'ID utente non valido.',
    'non_eliminabile' => 'Utente non eliminabile (admin, account corrente o con abbonamenti collegati).',
    'errore_db' => 'Errore database durante il salvataggio (email forse gia presente).',
    'salvataggio_fallito' => 'Salvataggio non riuscito.',
];

$messaggioASchermo = null;
$tipoMessaggioASchermo = null;

$successKey = $_GET['success'] ?? '';
$errorKey = $_GET['error'] ?? '';

if ($successKey !== '' && isset($messaggiSuccesso[$successKey])) {
    $messaggioASchermo = $messaggiSuccesso[$successKey];
    $tipoMessaggioASchermo = 'success';
} elseif ($errorKey !== '' && isset($messaggiErrore[$errorKey])) {
    $messaggioASchermo = $messaggiErrore[$errorKey];
    $tipoMessaggioASchermo = 'error';
}
?>

<?php require_once '../templates/header.php'; ?>
<?php require_once '../templates/screen_message.php'; ?>

<div class="flex min-h-[calc(100vh-4rem)]">

  <?php include '../templates/sidebar.php'; ?>

  <main class="flex-1 p-8">
    <h1 class="text-2xl font-semibold mb-6">Gestione Utenti</h1>

    <div class="bg-white rounded-xl shadow p-4">
      <details class="mb-4">
        <summary class="cursor-pointer inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded">
          + Aggiungi Utente
        </summary>

        <form method="post" class="mt-4 grid gap-3 md:grid-cols-4">
          <input type="hidden" name="form_action" value="aggiungi_utente">

          <input type="text" name="nome" class="border rounded px-3 py-2" placeholder="Nome" required>
          <input type="email" name="email" class="border rounded px-3 py-2" placeholder="Email" required>
          <input type="password" name="password" class="border rounded px-3 py-2" placeholder="Password" minlength="6" required>

          <div class="flex gap-2">
            <select name="ruolo" class="border rounded px-3 py-2 w-full">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">
              Salva
            </button>
          </div>
        </form>
      </details>

      <form method="get" class="mb-4 flex flex-wrap gap-2 items-center">
        <input
          type="text"
          name="q"
          value="<?= htmlspecialchars($ricerca) ?>"
          class="border rounded px-3 py-2 w-full md:w-1/3"
          placeholder="Cerca utente..."
        >
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-500">
          Cerca
        </button>
        <?php if ($ricerca !== ''): ?>
          <a href="public/admin/utenti.php')) ?>" class="inline-flex items-center rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
            Reset
          </a>
        <?php endif; ?>
      </form>

      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <table id="utentiTable" class="w-full text-sm text-center admin-table">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>Abbonamenti Attivi</th>
            <th>Ruolo</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (empty($utenti)): ?>
            <tr class="hover:bg-slate-50" data-static-row="true">
              <td colspan="5">Nessun utente trovato.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($utenti as $u): ?>
              <tr class="hover:bg-slate-50">
                <td><?= htmlspecialchars($u['nome']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= (int) $u['abbonamenti_attivi'] ?></td>
                <td class="font-medium text-slate-700"><?= htmlspecialchars(strtoupper($u['ruolo'])) ?></td>
                <td class="actions">
                  <?php
                    $isUtenteCorrente = (int) $u['id'] === (int) ($_SESSION['id'] ?? 0);
                    $isAdmin = strtolower((string) ($u['ruolo'] ?? '')) === 'admin';
                  ?>
                  <?php if ($isUtenteCorrente || $isAdmin): ?>
                    <span class="text-xs text-slate-400">-</span>
                  <?php else: ?>
                    <i class="fa fa-trash" title="Elimina utente"
                      onclick="confermaAzione('elimina', <?= (int) $u['id'] ?>)"></i>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php require_once '../templates/footer.php'; ?>
