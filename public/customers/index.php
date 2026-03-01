<?php
require 'check_session.php';
require_once '../../app/dal/AbbonamentoDal.php';
$title = 'Dashboard Utente';
require_once '../templates/header.php';

$abbonamentiDal = new AbbonamentoDal($pdo);
$abbonamenti = $abbonamentiDal->getByUtente((int) $_SESSION['id']);

$attivi = 0;
$inScadenza = 0;
$spesaMensile = 0.0;
$spesaPerCategoriaMap = [];
$notifiche = [];

$oggi = new DateTimeImmutable('today');
$limiteScadenza = $oggi->modify('+7 days');

foreach ($abbonamenti as $abbonamento) {
    if ((int) $abbonamento['attivo'] !== 1) {
        continue;
    }

    $attivi++;
    $spesaMensile += (float) ($abbonamento['costo'] ?? 0);
    $categoria = trim((string) ($abbonamento['categoria'] ?? ''));
    if ($categoria === '') {
        $categoria = 'Senza categoria';
    }
    $spesaPerCategoriaMap[$categoria] = ($spesaPerCategoriaMap[$categoria] ?? 0) + (float) ($abbonamento['costo'] ?? 0);

    $dataFine = DateTimeImmutable::createFromFormat('Y-m-d', (string) $abbonamento['data_fine']);
    if ($dataFine === false) {
        $dataFine = new DateTimeImmutable((string) $abbonamento['data_fine']);
    }

    if ($dataFine >= $oggi && $dataFine <= $limiteScadenza) {
        $inScadenza++;
        $notifiche[] = [
            'nome' => (string) ($abbonamento['servizio'] ?? 'Servizio'),
            'data_fine' => (string) ($abbonamento['data_fine'] ?? ''),
        ];
    }
}

$spesaAnnuale = $spesaMensile * 12;
$spesa_totale_ricorrente = $spesaMensile;

arsort($spesaPerCategoriaMap);
$spesa_per_categoria = [];
foreach ($spesaPerCategoriaMap as $categoria => $totale) {
    $spesa_per_categoria[] = [
        'categoria' => $categoria,
        'totale' => $totale,
    ];
}
?>

<div class="flex min-h-[calc(100vh-4rem)]">

  <?php include '../templates/sidebar_customer.php'; ?>

  <main class="flex-1 p-8 space-y-8">
    <div class="space-y-6">

      <div class="bg-indigo-600 text-white p-6 rounded-xl shadow flex justify-between items-center">
        <div>
          <p class="text-sm opacity-80">Abbonamenti Attivi</p>
          <p class="text-3xl font-bold"><?= (int) $attivi ?></p>
        </div>
        <i class="fa-solid fa-file-invoice text-4xl opacity-80" aria-hidden="true"></i>
      </div>

      <div class="bg-orange-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
        <div>
          <p class="text-sm opacity-80">In Scadenza (7gg)</p>
          <p class="text-3xl font-bold"><?= (int) $inScadenza ?></p>
        </div>
        <i class="fa-regular fa-clock text-4xl opacity-80" aria-hidden="true"></i>
      </div>

      <div class="bg-green-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
        <div>
          <p class="text-sm opacity-80">Spesa Mensile</p>
          <p class="text-3xl font-bold">
            &euro;<?= number_format($spesaMensile, 2, ',', '.') ?>
          </p>
        </div>
        <i class="fa-solid fa-calendar-days text-4xl opacity-80" aria-hidden="true"></i>
      </div>

      <div class="bg-purple-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
        <div>
          <p class="text-sm opacity-80">Spesa Annuale</p>
          <p class="text-3xl font-bold">
            &euro;<?= number_format($spesaAnnuale, 2, ',', '.') ?>
          </p>
        </div>
        <i class="fa-solid fa-money-check-dollar text-4xl opacity-80" aria-hidden="true"></i>
      </div>

    </div>

    <?php include '../templates/notifiche.php'; ?>

    <?php include '../templates/categorie.php'; ?>
  </main>
</div>

<?php require_once '../templates/footer.php'; ?>
