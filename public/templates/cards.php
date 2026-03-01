<div class="space-y-6">

  <div class="bg-indigo-600 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">Abbonamenti Attivi</p>
      <p class="text-3xl font-bold"><?= $tot ?></p>
    </div>
    <span class="text-4xl opacity-80">📄</span>
  </div>

  <div class="bg-orange-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">In Scadenza (7gg)</p>
      <p class="text-3xl font-bold"><?= $scad ?></p>
    </div>
    <span class="text-4xl opacity-80">⏰</span>
  </div>

  <div class="bg-green-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">Spesa Mensile</p>
      <p class="text-3xl font-bold">
        €<?= number_format($spesa_mensile, 2, ',', '.') ?>
      </p>
    </div>
    <span class="text-4xl opacity-80">📅</span>
  </div>

  <div class="bg-purple-500 text-white p-6 rounded-xl shadow flex justify-between items-center">
    <div>
      <p class="text-sm opacity-80">Spesa Annuale</p>
      <p class="text-3xl font-bold">
        €<?= number_format($spesa_annuale, 2, ',', '.') ?>
      </p>
    </div>
    <span class="text-4xl opacity-80">💳</span>
  </div>

</div>
