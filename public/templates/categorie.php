<div class="bg-white rounded-xl shadow p-6">
  <h2 class="font-semibold mb-4">Spesa ricorrente per categoria</h2>

  <div class="space-y-3">
    <div class="flex justify-between">
      <span class="text-slate-600">Totale ricorrente</span>
      <span class="font-medium text-indigo-600">
        &euro;<?= number_format((float)$spesa_totale_ricorrente, 2, ',', '.')?> 
      </span>
    </div>

    <?php if (empty($spesa_per_categoria)): ?>
      <p class="text-slate-500">Nessuna spesa ricorrente disponibile.</p>
    <?php else: ?>
      <?php foreach ($spesa_per_categoria as $row): ?>
        <div class="flex justify-between">
          <span class="text-slate-600"><?= htmlspecialchars($row['categoria']) ?></span>
          <span class="font-medium text-indigo-600">
            &euro;<?= number_format((float)$row['totale'], 2, ',', '.') ?> / mese
          </span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
