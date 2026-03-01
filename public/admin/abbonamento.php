<?php
require 'check_session.php';
require_once '../../app/dal/AbbonamentoDal.php';
$title = 'Gestione Abbonamenti';

$abbonamentiDal = new AbbonamentoDal($pdo);

$filtriDisponibili = [
    'attivi' => 'Attivi',
    'disattivati' => 'Disattivati',
    'archiviati' => 'Archiviati',
    'scadenza_annuale' => 'In scadenza annuali',
    'tutti' => 'Tutti',
];
$frequenzeDisponibili = [
    'mensile' => 'Mensile',
    'annuale' => 'Annuale',
];

$filtroInput = $_GET['filtro'] ?? $_POST['filtro'] ?? 'attivi';
$filtro = array_key_exists($filtroInput, $filtriDisponibili) ? $filtroInput : 'attivi';

$redirectConFiltro = function (array $params = []) use ($filtro): void {
    $query = http_build_query(array_merge(['filtro' => $filtro], $params));
    header('Location: ' . url('public/admin/abbonamento.php') . '?' . $query);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'aggiungi_abbonamento') {
    $utenteId = filter_input(INPUT_POST, 'utente_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $servizioId = filter_input(INPUT_POST, 'servizio_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $dataFine = trim((string) ($_POST['data_fine'] ?? ''));
    $frequenza = strtolower(trim((string) ($_POST['frequenza'] ?? 'mensile')));

    $dataValida = DateTime::createFromFormat('Y-m-d', $dataFine) !== false;
    $frequenzaValida = array_key_exists($frequenza, $frequenzeDisponibili);

    if ($utenteId === false || $utenteId === null || $servizioId === false || $servizioId === null || !$dataValida || !$frequenzaValida) {
        $redirectConFiltro(['error' => 'campi_non_validi']);
    }

    try {
        if ($abbonamentiDal->aggiungi((int) $utenteId, (int) $servizioId, $dataFine, $frequenza)) {
            $redirectConFiltro(['success' => 'aggiunto']);
        }
        $redirectConFiltro(['error' => 'salvataggio_fallito']);
    } catch (PDOException $e) {
        $redirectConFiltro(['error' => 'errore_db']);
    }
}

if (isset($_GET['riattiva'])) {
    $id = filter_input(INPUT_GET, 'riattiva', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $riattivaDataFine = trim((string) ($_GET['riattiva_data'] ?? ''));
    $riattivaData = DateTime::createFromFormat('Y-m-d', $riattivaDataFine);
    $riattivaDataValida = $riattivaData !== false && $riattivaData->format('Y-m-d') === $riattivaDataFine;

    if ($id === false || $id === null) {
        $redirectConFiltro(['error' => 'id_non_valido']);
    }

    if (!$riattivaDataValida) {
        $redirectConFiltro(['error' => 'data_riattiva_non_valida']);
    }

    if ($abbonamentiDal->riattiva((int) $id, $riattivaDataFine)) {
        $redirectConFiltro(['success' => 'riattivato']);
    }

    $redirectConFiltro(['error' => 'non_trovato_o_gia_attivo']);
}

$azioni = [
    'archivia' => ['metodo' => 'archivia', 'success' => 'archiviato', 'error' => 'non_trovato_o_gia_archiviato'],
    'disattiva' => ['metodo' => 'disattiva', 'success' => 'disattivato', 'error' => 'non_trovato_o_gia_disattivato'],
    'dearchivia' => ['metodo' => 'dearchivia', 'success' => 'dearchiviato', 'error' => 'non_trovato_o_non_archiviato'],
    'elimina' => ['metodo' => 'elimina', 'success' => 'eliminato', 'error' => 'non_trovato'],
];

foreach ($azioni as $param => $config) {
    if (!isset($_GET[$param])) {
        continue;
    }

    $id = filter_input(INPUT_GET, $param, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($id === false || $id === null) {
        $redirectConFiltro(['error' => 'id_non_valido']);
    }

    $metodo = $config['metodo'];
    if ($abbonamentiDal->$metodo((int) $id)) {
        $redirectConFiltro(['success' => $config['success']]);
    }

    $redirectConFiltro(['error' => $config['error']]);
}

$abbonamenti = $abbonamentiDal->getForAdmin($filtro);
$utentiPerSelect = $abbonamentiDal->getUtentiPerSelect();
$serviziPerSelect = $abbonamentiDal->getServiziPerSelect();

$messaggiSuccesso = [
    'aggiunto' => 'Abbonamento aggiunto correttamente.',
    'archiviato' => 'Abbonamento archiviato correttamente.',
    'disattivato' => 'Abbonamento disattivato correttamente.',
    'riattivato' => 'Abbonamento riattivato correttamente.',
    'dearchiviato' => 'Abbonamento rimosso dagli archiviati.',
    'eliminato' => 'Abbonamento eliminato correttamente.',
];

$messaggiErrore = [
    'campi_non_validi' => 'Campi non validi. Controlla i dati inseriti.',
    'id_non_valido' => 'ID abbonamento non valido.',
    'data_riattiva_non_valida' => 'Data nuova scadenza non valida.',
    'errore_db' => 'Errore database durante il salvataggio.',
    'salvataggio_fallito' => 'Salvataggio non riuscito.',
    'non_trovato_o_gia_archiviato' => 'Abbonamento non trovato oppure gia archiviato.',
    'non_trovato_o_gia_disattivato' => 'Abbonamento non trovato oppure gia disattivato.',
    'non_trovato_o_gia_attivo' => 'Abbonamento non trovato oppure gia attivo.',
    'non_trovato_o_non_archiviato' => 'Abbonamento non trovato oppure non archiviato.',
    'non_trovato' => 'Abbonamento non trovato.',
];

$messaggioASchermo = null;
$tipoMessaggioASchermo = null;

if (isset($_GET['success']) && isset($messaggiSuccesso[$_GET['success']])) {
    $messaggioASchermo = $messaggiSuccesso[$_GET['success']];
    $tipoMessaggioASchermo = 'success';
} elseif (isset($_GET['error']) && isset($messaggiErrore[$_GET['error']])) {
    $messaggioASchermo = $messaggiErrore[$_GET['error']];
    $tipoMessaggioASchermo = 'error';
}
?>

<?php require_once '../templates/header.php'; ?>
<?php require_once '../templates/screen_message.php'; ?>

<div class="flex min-h-[calc(100vh-4rem)]">

  <?php include '../templates/sidebar.php'; ?>

  <main class="flex-1 p-8">

    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold">Gestione Abbonamenti</h1>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
      <details class="mb-4">
        <summary class="cursor-pointer inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded">
          + Aggiungi Abbonamento
        </summary>

        <?php if (empty($utentiPerSelect) || empty($serviziPerSelect)): ?>
          <p class="mt-4 text-sm text-slate-600">Servono almeno un utente e un servizio per creare un abbonamento.</p>
        <?php else: ?>
          <form method="post" class="mt-4 grid gap-3 md:grid-cols-5">
            <input type="hidden" name="form_action" value="aggiungi_abbonamento">
            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">

            <select name="utente_id" class="border rounded px-3 py-2" required>
              <option value="">Seleziona utente</option>
              <?php foreach ($utentiPerSelect as $utente): ?>
                <option value="<?= (int) $utente['id'] ?>"><?= htmlspecialchars($utente['nome']) ?></option>
              <?php endforeach; ?>
            </select>

            <select name="servizio_id" class="border rounded px-3 py-2" required>
              <option value="">Seleziona servizio</option>
              <?php foreach ($serviziPerSelect as $servizio): ?>
                <option value="<?= (int) $servizio['id'] ?>"><?= htmlspecialchars($servizio['nome']) ?></option>
              <?php endforeach; ?>
            </select>

            <input type="date" name="data_fine" class="border rounded px-3 py-2" required>

            <select name="frequenza" class="border rounded px-3 py-2" required>
              <?php foreach ($frequenzeDisponibili as $valoreFrequenza => $labelFrequenza): ?>
                <option value="<?= htmlspecialchars($valoreFrequenza) ?>"><?= htmlspecialchars($labelFrequenza) ?></option>
              <?php endforeach; ?>
            </select>

            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">
              Salva
            </button>
          </form>
        <?php endif; ?>
      </details>

      <div class="flex flex-wrap gap-2 mb-4">
        <?php foreach ($filtriDisponibili as $chiave => $label): ?>
          <?php
          $attivo = $filtro === $chiave;
          $classi = $attivo
              ? 'px-3 py-2 rounded bg-indigo-600 text-white text-sm'
              : 'px-3 py-2 rounded border text-slate-700 text-sm hover:bg-slate-100';
          $link = url('public/admin/abbonamento.php') . '?filtro=' . urlencode($chiave);
          ?>
          <a href="<?= htmlspecialchars($link) ?>" class="<?= $classi ?>">
            <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <input
        id="abbonamentiSearch"
        data-table-search="abbonamentiTable"
        data-no-results="abbonamentiNoResults"
        type="text"
        class="border rounded px-3 py-2 mb-4 w-full md:w-1/3"
        placeholder="Cerca abbonamento..."
      >

      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <table id="abbonamentiTable" class="w-full text-sm text-center admin-table">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th>Utente</th>
            <th>Servizio</th>
            <th>Stato</th>
            <th>Fine</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (empty($abbonamenti)): ?>
            <tr class="hover:bg-slate-50" data-static-row="true">
              <td colspan="5">Nessun abbonamento trovato per il filtro selezionato.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($abbonamenti as $a):
              $oggi = new DateTimeImmutable('today');
              $fine = DateTimeImmutable::createFromFormat('Y-m-d', (string) $a['data_fine']);
              if ($fine === false) {
                  $fine = new DateTimeImmutable((string) $a['data_fine']);
              }
              $giorniAllaScadenza = (int) $oggi->diff($fine)->format('%r%a');
              $isScaduto = $fine < $oggi;
              $isArchiviato = (int) $a['archiviato'] === 1;
              $isAttivo = (int) $a['attivo'] === 1;
              $classeRiga = (!$isArchiviato && (!$isAttivo || $isScaduto))
                  ? 'bg-red-50 hover:bg-red-100 js-abbonamento-row'
                  : 'hover:bg-slate-50 js-abbonamento-row';
            ?>
              <tr class="<?= $classeRiga ?>">
                <td><?= htmlspecialchars($a['nomeutente']) ?></td>
                <td><?= htmlspecialchars($a['nomeservizio']) ?></td>

                <td>
                  <?php if ($isArchiviato): ?>
                    <span class="badge bg-slate-500">Archiviato</span>
                  <?php elseif (!$isAttivo): ?>
                    <span class="badge disattivo">Disattivato</span>
                  <?php elseif ($isScaduto): ?>
                    <span class="badge scaduto">Scaduto</span>
                  <?php elseif ($giorniAllaScadenza <= 7): ?>
                    <span class="badge scadenza">In scadenza</span>
                  <?php else: ?>
                    <span class="badge attivo">Attivo</span>
                  <?php endif; ?>
                </td>

                <td><?= htmlspecialchars($a['data_fine']) ?></td>

                <td class="actions">
                  <?php if (!$isArchiviato): ?>
                    <i class="fa fa-archive" title="Archivia"
                      onclick="confermaAzione('archivia', <?= (int) $a['id'] ?>)"></i>
                  <?php endif; ?>

                  <?php if ($isAttivo && !$isArchiviato): ?>
                    <i class="fa fa-ban" title="Disattiva"
                      onclick="confermaAzione('disattiva', <?= (int) $a['id'] ?>)"></i>
                  <?php endif; ?>

                  <?php if (!$isAttivo && !$isArchiviato): ?>
                    <i class="fa fa-play" title="Riattiva"
                      onclick="confermaAzione('riattiva', <?= (int) $a['id'] ?>)"></i>
                  <?php endif; ?>

                  <?php if ($isArchiviato): ?>
                    <i class="fa fa-box-open" title="Rimuovi da archiviati"
                      onclick="confermaAzione('dearchivia', <?= (int) $a['id'] ?>)"></i>
                  <?php endif; ?>

                  <i class="fa fa-trash" title="Elimina"
                    onclick="confermaAzione('elimina', <?= (int) $a['id'] ?>)"></i>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="abbonamentiNoResults" class="hidden hover:bg-slate-50" data-static-row="true">
              <td colspan="5">Nessun risultato per la ricerca.</td>
            </tr>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<?php require_once '../templates/footer.php'; ?>
