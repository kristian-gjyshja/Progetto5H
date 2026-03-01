<?php
require 'check_session.php';
require_once '../../app/dal/AbbonamentoDal.php';
$title = 'I Miei Abbonamenti';

$abbonamentiDal = new AbbonamentoDal($pdo);
$utenteId = (int) ($_SESSION['id'] ?? 0);

$filtriDisponibili = [
    'attivi' => 'Attivi',
    'disattivati' => 'Disattivati',
    'scadenza_annuale' => 'In scadenza annuali',
    'tutti' => 'Tutti',
];

$filtroInput = $_GET['filtro'] ?? $_POST['filtro'] ?? 'attivi';
$filtro = array_key_exists($filtroInput, $filtriDisponibili) ? $filtroInput : 'attivi';

$redirectConFiltro = function (array $params = []) use ($filtro): void {
    $query = http_build_query(array_merge(['filtro' => $filtro], $params));
    header('Location: ' . url('public/customers/abbonamenti.php') . '?' . $query);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'aggiungi_abbonamento') {
    $servizioId = filter_input(INPUT_POST, 'servizio_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $dataFine = trim((string) ($_POST['data_fine'] ?? ''));

    $data = DateTime::createFromFormat('Y-m-d', $dataFine);
    $dataValida = $data !== false && $data->format('Y-m-d') === $dataFine;

    if ($servizioId === false || $servizioId === null || !$dataValida) {
        $redirectConFiltro(['error' => 'campi_non_validi']);
    }

    try {
        if ($abbonamentiDal->aggiungi($utenteId, (int) $servizioId, $dataFine)) {
            $redirectConFiltro(['success' => 'aggiunto']);
        }
        $redirectConFiltro(['error' => 'salvataggio_fallito']);
    } catch (PDOException $e) {
        $redirectConFiltro(['error' => 'errore_db']);
    }
}

if (isset($_GET['disattiva'])) {
    $id = filter_input(INPUT_GET, 'disattiva', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($id === false || $id === null) {
        $redirectConFiltro(['error' => 'id_non_valido']);
    }

    if ($abbonamentiDal->disattivaPerUtente((int) $id, $utenteId)) {
        $redirectConFiltro(['success' => 'disattivato']);
    }

    $redirectConFiltro(['error' => 'non_trovato_o_gia_disattivato']);
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

    if ($abbonamentiDal->riattivaPerUtente((int) $id, $utenteId, $riattivaDataFine)) {
        $redirectConFiltro(['success' => 'riattivato']);
    }

    $redirectConFiltro(['error' => 'non_trovato_o_gia_attivo']);
}

if (isset($_GET['elimina'])) {
    $id = filter_input(INPUT_GET, 'elimina', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($id === false || $id === null) {
        $redirectConFiltro(['error' => 'id_non_valido']);
    }

    if ($abbonamentiDal->eliminaPerUtente((int) $id, $utenteId)) {
        $redirectConFiltro(['success' => 'eliminato']);
    }

    $redirectConFiltro(['error' => 'non_trovato_o_non_eliminabile']);
}

$serviziPerSelect = $abbonamentiDal->getServiziPerSelect();

if ($filtro === 'scadenza_annuale') {
    $abbonamentiFiltrati = $abbonamentiDal->getInScadenzaAnnualiByUtente($utenteId);
} else {
    $abbonamenti = $abbonamentiDal->getByUtente($utenteId);

    $abbonamentiFiltrati = array_values(array_filter($abbonamenti, function (array $abbonamento) use ($filtro): bool {
        if ($filtro === 'attivi') {
            return (int) $abbonamento['attivo'] === 1;
        }

        if ($filtro === 'disattivati') {
            return (int) $abbonamento['attivo'] === 0;
        }

        return true;
    }));
}

$messaggiSuccesso = [
    'aggiunto' => 'Abbonamento aggiunto correttamente.',
    'disattivato' => 'Abbonamento messo in pausa correttamente.',
    'riattivato' => 'Abbonamento riattivato correttamente.',
    'eliminato' => 'Abbonamento eliminato correttamente.',
];

$messaggiErrore = [
    'campi_non_validi' => 'Campi non validi. Controlla i dati inseriti.',
    'id_non_valido' => 'ID abbonamento non valido.',
    'data_riattiva_non_valida' => 'Data nuova scadenza non valida.',
    'errore_db' => 'Errore database durante il salvataggio.',
    'salvataggio_fallito' => 'Salvataggio non riuscito.',
    'non_trovato_o_gia_disattivato' => 'Abbonamento non trovato oppure gia disattivato.',
    'non_trovato_o_gia_attivo' => 'Abbonamento non trovato oppure gia attivo.',
    'non_trovato_o_non_eliminabile' => 'Abbonamento non trovato oppure non eliminabile.',
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

require_once '../templates/header.php';
?>

<?php require_once '../templates/screen_message.php'; ?>

<div class="flex min-h-[calc(100vh-4rem)]">

  <?php include '../templates/sidebar_customer.php'; ?>

  <main class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold">I miei abbonamenti</h1>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
      <details class="mb-4">
        <summary class="cursor-pointer inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded">
          + Aggiungi Abbonamento
        </summary>

        <?php if (empty($serviziPerSelect)): ?>
          <p class="mt-4 text-sm text-slate-600">Non ci sono servizi disponibili per creare un abbonamento.</p>
        <?php else: ?>
          <form method="post" class="mt-4 grid gap-3 md:grid-cols-3">
            <input type="hidden" name="form_action" value="aggiungi_abbonamento">
            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">

            <select name="servizio_id" class="border rounded px-3 py-2" required>
              <option value="">Seleziona servizio</option>
              <?php foreach ($serviziPerSelect as $servizio): ?>
                <option value="<?= (int) $servizio['id'] ?>"><?= htmlspecialchars($servizio['nome']) ?></option>
              <?php endforeach; ?>
            </select>

            <input type="date" name="data_fine" class="border rounded px-3 py-2" required>

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
          $link = url('public/customers/abbonamenti.php') . '?filtro=' . urlencode($chiave);
          ?>
          <a href="<?= htmlspecialchars($link) ?>" class="<?= $classi ?>">
            <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <input
        id="customerAbbonamentiSearch"
        data-table-search="customerAbbonamentiTable"
        data-no-results="customerAbbonamentiNoResults"
        type="text"
        class="border rounded px-3 py-2 mb-4 w-full md:w-1/3"
        placeholder="Cerca abbonamento..."
      >

      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <table id="customerAbbonamentiTable" class="w-full text-sm text-center admin-table">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th>Servizio</th>
            <th>Categoria</th>
            <th>Stato</th>
            <th>Fine</th>
            <th>Spesa</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (empty($abbonamentiFiltrati)): ?>
            <tr class="hover:bg-slate-50" data-static-row="true">
              <td colspan="6">Nessun abbonamento trovato per il filtro selezionato.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($abbonamentiFiltrati as $a):
              $oggi = new DateTimeImmutable('today');
              $fine = DateTimeImmutable::createFromFormat('Y-m-d', (string) $a['data_fine']);
              if ($fine === false) {
                  $fine = new DateTimeImmutable((string) $a['data_fine']);
              }
              $giorniAllaScadenza = (int) $oggi->diff($fine)->format('%r%a');
              $isScaduto = $fine < $oggi;
              $isAttivo = (int) $a['attivo'] === 1;
              $isEliminabile = !$isAttivo || $isScaduto;
              $frequenza = strtolower(trim((string) ($a['frequenza'] ?? 'mensile')));
              if (!in_array($frequenza, ['mensile', 'annuale'], true)) {
                  $frequenza = 'mensile';
              }
              $suffissoSpesa = $frequenza === 'annuale' ? 'anno' : 'mese';
            ?>
              <?php $classeRiga = (!$isAttivo || $isScaduto) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-slate-50'; ?>
              <tr class="<?= $classeRiga ?>">
                <td><?= htmlspecialchars($a['servizio']) ?></td>
                <td><?= htmlspecialchars($a['categoria'] ?? 'Senza categoria') ?></td>

                <td>
                  <?php if (!$isAttivo): ?>
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
                <td>
                  &euro;<?= number_format((float) ($a['costo'] ?? 0), 2, ',', '.') ?>
                  <span class="text-xs text-slate-500">/ <?= htmlspecialchars($suffissoSpesa) ?></span>
                </td>
                <td class="actions">
                  <?php if ($isAttivo): ?>
                    <i class="fa fa-pause" title="Metti in pausa"
                      onclick="confermaAzione('disattiva', <?= (int) $a['id'] ?>)"></i>
                  <?php else: ?>
                    <i class="fa fa-play" title="Riattiva"
                      onclick="confermaAzione('riattiva', <?= (int) $a['id'] ?>)"></i>
                  <?php endif; ?>

                  <?php if ($isEliminabile): ?>
                    <i class="fa fa-trash" title="Elimina"
                      onclick="confermaAzione('elimina', <?= (int) $a['id'] ?>)"></i>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="customerAbbonamentiNoResults" class="hidden hover:bg-slate-50" data-static-row="true">
              <td colspan="6">Nessun risultato per la ricerca.</td>
            </tr>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php require_once '../templates/footer.php'; ?>
