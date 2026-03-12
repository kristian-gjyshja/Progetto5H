<?php
require 'check_session.php';
require_once '../../app/dal/AbbonamentoDal.php';
$title = 'Dashboard Admin';
require_once '../templates/header.php';

$abbonamentiDal  = new AbbonamentoDal($pdo);
$notifiche = $abbonamentiDal->inScadenza();
$tot = count($abbonamentiDal->getAll());
$scad = count($notifiche);
$spesa_mensile = $abbonamentiDal->spesaMensile();
$spesa_annuale = $abbonamentiDal->spesaAnnuale();
$spesa_totale_ricorrente = $abbonamentiDal->spesaTotaleRicorrente();
$spesa_per_categoria = $abbonamentiDal->spesaRicorrentePerCategoria();
?>


<div class="flex min-h-[calc(100vh-4rem)]">

  <?php include '../templates/sidebar.php'; ?>

  <main class="flex-1 p-8 space-y-8">

    <?php include '../templates/cards.php'; ?>

    <?php include '../templates/notifiche.php'; ?>

    <?php include '../templates/categorie.php'; ?>
  </main>
</div>

<?php require_once '../templates/footer.php'; ?>
