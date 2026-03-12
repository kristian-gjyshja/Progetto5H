<?php
require 'check_session.php';
require_once '../../app/dal/ServizioDal.php';
$title = 'Configurazioni Servizi';

$servizioDal = new ServizioDAL($pdo);
$ricerca = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$queryBase = [];
if ($ricerca !== '') {
    $queryBase['q'] = $ricerca;
}

$redirect = function (array $params = []) use ($queryBase): void {
    $query = http_build_query(array_merge($queryBase, $params));
    $query = $query !== '' ? ('?' . $query) : '';
    header('Location: public/admin/configurazioni.php?' . $query);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'aggiungi_servizio') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $categoria = trim((string) ($_POST['categoria'] ?? ''));
    $costoInput = trim((string) ($_POST['costo'] ?? ''));
    $costoNormalizzato = str_replace(',', '.', $costoInput);

    $costoValido = is_numeric($costoNormalizzato) && (float) $costoNormalizzato > 0;
    if ($nome === '' || $categoria === '' || !$costoValido) {
        $redirect(['error' => 'campi_non_validi']);
    }

    try {
        if ($servizioDal->aggiungi($nome, $categoria, (float) $costoNormalizzato)) {
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

    try {
        if ($servizioDal->elimina((int) $id)) {
            $redirect(['success' => 'eliminato']);
        }
        $redirect(['error' => 'non_trovato_o_collegato']);
    } catch (PDOException $e) {
        $redirect(['error' => 'errore_db']);
    }
}

$serviziAttivi = $servizioDal->getAll();
$serviziAttivi = $serviziAttivi ?? [];
if ($ricerca !== '') {
    $needle = $ricerca;
    $serviziAttivi = array_values(array_filter($serviziAttivi, function (array $s) use ($needle): bool {
        $haystack = implode(' ', [
            $s['id'] ?? '',
            $s['nome'] ?? '',
            $s['categoria'] ?? '',
            $s['costo'] ?? '',
        ]);
        return $haystack !== '' && stripos($haystack, $needle) !== false;
    }));
}
$serviziNonRinnovati = $servizioDal->getNonRinnovatiUltimoAnno();

$messaggiSuccesso = [
    'aggiunto' => 'Servizio aggiunto correttamente.',
    'eliminato' => 'Servizio eliminato correttamente.',
];

$messaggiErrore = [
    'campi_non_validi' => 'Campi non validi. Controlla i dati inseriti.',
    'id_non_valido' => 'ID servizio non valido.',
    'non_trovato_o_collegato' => 'Servizio non trovato oppure collegato ad abbonamenti esistenti.',
    'errore_db' => 'Errore database durante il salvataggio.',
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

require_once '../templates/header.php';
?>

<?php require_once '../templates/screen_message.php'; ?>

<div class="flex min-h-[calc(100vh-4rem)]">

  <?php include '../templates/sidebar.php'; ?>

  <main class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold">Configurazioni - Servizi</h1>
    </div>

    <div class="bg-white rounded-xl shadow p-4 mb-8">
      <details class="mb-4">
        <summary class="cursor-pointer inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded">
          + Aggiungi Servizio
        </summary>

        <form method="post" class="mt-4 grid gap-3 md:grid-cols-4">
          <input type="hidden" name="form_action" value="aggiungi_servizio">

          <input type="text" name="nome" class="border rounded px-3 py-2" placeholder="Nome servizio" required>
          <input type="text" name="categoria" class="border rounded px-3 py-2" placeholder="Categoria" required>
          <input
            type="text"
            name="costo"
            class="border rounded px-3 py-2"
            placeholder="Costo (es. 9,99)"
            inputmode="decimal"
            required
          >

          <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">
            Salva
          </button>
        </form>
      </details>

      <h2 class="text-lg font-semibold text-slate-800 mb-3">Catalogo servizi</h2>

      <form method="get" class="mb-4 flex flex-wrap gap-2 items-center">
        <input
          type="text"
          name="q"
          value="<?= htmlspecialchars($ricerca) ?>"
          class="border rounded px-3 py-2 w-full md:w-1/3"
          placeholder="Cerca servizio..."
        >
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-500">
          Cerca
        </button>
        <?php if ($ricerca !== ''): ?>
          <a href="public/admin/configurazioni.php')) ?>" class="inline-flex items-center rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
            Reset
          </a>
        <?php endif; ?>
      </form>

      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <table id="serviziTable" class="w-full text-sm text-center admin-table">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Costo</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (empty($serviziAttivi)): ?>
            <tr class="hover:bg-slate-50" data-static-row="true">
              <td colspan="4">Nessun servizio trovato.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($serviziAttivi as $s): ?>
              <tr class="hover:bg-slate-50">
                <td><?= htmlspecialchars($s['nome']) ?></td>
                <td><?= htmlspecialchars($s['categoria'] ?? 'Senza categoria') ?></td>
                <td><?= number_format((float) $s['costo'], 2, ',', '.') ?> &euro;</td>
                <td class="actions">
                  <i class="fa fa-trash" title="Elimina servizio"
                    onclick="confermaAzione('elimina', <?= (int) $s['id'] ?>)"></i>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>

    <h2 class="text-lg font-semibold text-slate-800 mb-3">Servizi non rinnovati nell'ultimo anno</h2>
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full text-sm text-center admin-table">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Costo</th>
            <th>Ultimo rinnovo</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (empty($serviziNonRinnovati)): ?>
            <tr class="hover:bg-slate-50" data-static-row="true">
              <td colspan="4">Nessun servizio trovato.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($serviziNonRinnovati as $s): ?>
              <tr class="hover:bg-slate-50">
                <td><?= htmlspecialchars($s['nome']) ?></td>
                <td><?= htmlspecialchars($s['categoria'] ?? 'Senza categoria') ?></td>
                <td><?= number_format((float) $s['costo'], 2, ',', '.') ?> &euro;</td>
                <td><?= $s['ultimo_rinnovo'] ? htmlspecialchars($s['ultimo_rinnovo']) : 'Mai rinnovato' ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<?php require_once '../templates/footer.php'; ?>
