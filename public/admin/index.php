<?php
require 'check_session.php';
require_once '../../app/dal/AbbonamentoDal.php';
$title = 'Dashboard Admin';
require_once '../templates/header.php';

$abbonamenti  = new AbbonamentoDal($pdo);
$tot = count($abbonamenti->getAll());
$scad = count($abbonamenti->inScadenza());
$spesa_mensile = $abbonamenti->spesaMensile();
$spesa_annuale = $abbonamenti->spesaAnnuale();
$spesa_totale_ricorrente = $abbonamenti->spesaTotaleRicorrente();
$spesa_per_categoria = $abbonamenti->spesaRicorrentePerCategoria();
$notifiche = $abbonamenti->inScadenza();
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
