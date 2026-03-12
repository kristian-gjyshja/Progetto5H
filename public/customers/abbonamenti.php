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
$ricerca = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$queryBase = ['filtro' => $filtro];
if ($ricerca !== '') {
    $queryBase['q'] = $ricerca;
}

$redirectConFiltro = function (array $params = []) use ($queryBase): void {
    $query = http_build_query(array_merge($queryBase, $params));
    header('Location: public/customers/abbonamenti.php' . '?' . $query);
    exit();
};

$isValidDate = function (string $value): bool {
    $data = DateTime::createFromFormat('Y-m-d', $value);
    return $data !== false && $data->format('Y-m-d') === $value;
};

$parseDate = function (string $value): ?DateTimeImmutable {
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $data = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($data !== false) {
        return $data;
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Exception $e) {
        return null;
    }
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'aggiungi_abbonamento') {
    $servizioId = filter_input(INPUT_POST, 'servizio_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $dataFine = trim((string) ($_POST['data_fine'] ?? ''));

    $dataValida = $isValidDate($dataFine);

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
    $riattivaDataValida = $isValidDate($riattivaDataFine);

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
if ($ricerca !== '') {
    $needle = $ricerca;
    $abbonamentiFiltrati = array_values(array_filter($abbonamentiFiltrati, function (array $a) use ($needle): bool {
        $haystack = implode(' ', [
            $a['id'] ?? '',
            $a['servizio'] ?? '',
            $a['categoria'] ?? '',
            $a['data_fine'] ?? '',
            $a['frequenza'] ?? '',
            $a['costo'] ?? '',
        ]);
        return $haystack !== '' && stripos($haystack, $needle) !== false;
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
          if ($ricerca !== '') {
              $link .= '&q=' . urlencode($ricerca);
          }
          ?>
          <a href="<?= htmlspecialchars($link) ?>" class="<?= $classi ?>">
            <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <form method="get" class="mb-4 flex flex-wrap gap-2 items-center">
        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
        <input
          type="text"
          name="q"
          value="<?= htmlspecialchars($ricerca) ?>"
          class="border rounded px-3 py-2 w-full md:w-1/3"
          placeholder="Cerca abbonamento..."
        >
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-500">
          Cerca
        </button>
        <?php if ($ricerca !== ''): ?>
          <a href="public/customers/abbonamenti.php') . '?filtro=' . urlencode($filtro)) ?>" class="inline-flex items-center rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
            Reset
          </a>
        <?php endif; ?>
      </form>

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
            <?php $oggi = new DateTimeImmutable('today'); ?>
            <?php foreach ($abbonamentiFiltrati as $a):
              $fine = $parseDate((string) ($a['data_fine'] ?? '')) ?? $oggi;
              $giorniAllaScadenza = (int) $oggi->diff($fine)->format('%r%a');
              $isScaduto = $fine < $oggi;
              $isAttivo = (int) $a['attivo'] === 1;
              $isEliminabile = !$isAttivo || $isScaduto;
              $frequenza = strtolower(trim((string) ($a['frequenza'] ?? 'mensile')));
              $frequenza = in_array($frequenza, ['mensile', 'annuale'], true) ? $frequenza : 'mensile';
              $suffissoSpesa = $frequenza === 'annuale' ? 'anno' : 'mese';
              $servizioLabel = trim((string) ($a['servizio'] ?? '')) ?: 'Servizio non disponibile';
              $categoriaLabel = trim((string) ($a['categoria'] ?? '')) ?: 'Senza categoria';
              $costo = (float) ($a['costo'] ?? 0);
            ?>
              <?php $classeRiga = (!$isAttivo || $isScaduto) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-slate-50'; ?>
              <tr class="<?= $classeRiga ?>">
                <td><?= htmlspecialchars($servizioLabel) ?></td>
                <td><?= htmlspecialchars($categoriaLabel) ?></td>

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
                  &euro;<?= number_format($costo, 2, ',', '.') ?>
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
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php require_once '../templates/footer.php'; ?>
