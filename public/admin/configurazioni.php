<?php
require 'check_session.php';
require_once '../../app/dal/ServizioDal.php';
$title = 'Configurazioni Servizi';

$servizioDal = new ServizioDAL($pdo);

$redirect = function (array $params = []): void {
    $query = $params ? ('?' . http_build_query($params)) : '';
    header('Location: ' . url('public/admin/configurazioni.php') . $query);
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

$serviziAttivi = $servizioDal->getAll();
$serviziNonRinnovati = $servizioDal->getNonRinnovatiUltimoAnno();

$messaggiSuccesso = [
    'aggiunto' => 'Servizio aggiunto correttamente.',
];

$messaggiErrore = [
    'campi_non_validi' => 'Campi non validi. Controlla i dati inseriti.',
    'errore_db' => 'Errore database durante il salvataggio.',
    'salvataggio_fallito' => 'Salvataggio non riuscito.',
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

      <input
        id="serviziSearch"
        data-table-search="serviziTable"
        data-no-results="serviziNoResults"
        type="text"
        class="border rounded px-3 py-2 mb-4 w-full md:w-1/3"
        placeholder="Cerca servizio..."
      >

      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <table id="serviziTable" class="w-full text-sm text-center admin-table">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Costo</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (empty($serviziAttivi)): ?>
            <tr class="hover:bg-slate-50" data-static-row="true">
              <td colspan="3">Nessun servizio trovato.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($serviziAttivi as $s): ?>
              <tr class="hover:bg-slate-50">
                <td><?= htmlspecialchars($s['nome']) ?></td>
                <td><?= htmlspecialchars($s['categoria'] ?? 'Senza categoria') ?></td>
                <td><?= number_format((float) $s['costo'], 2, ',', '.') ?> &euro;</td>
              </tr>
            <?php endforeach; ?>
            <tr id="serviziNoResults" class="hidden hover:bg-slate-50" data-static-row="true">
              <td colspan="3">Nessun risultato per la ricerca.</td>
            </tr>
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
