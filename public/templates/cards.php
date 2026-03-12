<div class="space-y-6">

  <div class="bg-indigo-600 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">Abbonamenti Attivi</p>
      <p class="text-3xl font-bold"><?= (int) $tot ?></p>
    </div>
    <i class="fa-solid fa-file-invoice text-4xl opacity-80" aria-hidden="true"></i>
  </div>

  <div class="bg-orange-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">In Scadenza (7gg)</p>
      <p class="text-3xl font-bold"><?= (int) $scad ?></p>
    </div>
    <i class="fa-regular fa-clock text-4xl opacity-80" aria-hidden="true"></i>
  </div>

  <div class="bg-green-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">Spesa Mensile</p>
      <p class="text-3xl font-bold">
        &euro;<?= number_format($spesa_mensile, 2, ',', '.') ?>
      </p>
    </div>
    <i class="fa-solid fa-calendar-days text-4xl opacity-80" aria-hidden="true"></i>
  </div>

  <div class="bg-purple-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">Spesa Annuale</p>
      <p class="text-3xl font-bold">
        &euro;<?= number_format($spesa_annuale, 2, ',', '.') ?>
      </p>
    </div>
    <i class="fa-solid fa-money-check-dollar text-4xl opacity-80" aria-hidden="true"></i>
  </div>

</div>
