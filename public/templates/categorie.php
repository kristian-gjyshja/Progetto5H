<div class="panel">
  <h2 class="panel-title">Spesa ricorrente per categoria</h2>

  <div class="panel-list">
    <div class="panel-row">
      <span class="panel-label">Totale ricorrente</span>
      <span class="panel-value">
        &euro;<?= number_format((float) $spesa_totale_ricorrente, 2, ',', '.') ?>
      </span>
    </div>

    <?php if (empty($spesa_per_categoria)): ?>
      <p class="panel-muted">Nessuna spesa ricorrente disponibile.</p>
    <?php else: ?>
      <?php foreach ($spesa_per_categoria as $row): ?>
        <div class="panel-row">
          <span class="panel-label"><?= htmlspecialchars($row['categoria']) ?></span>
          <span class="panel-value">
            &euro;<?= number_format((float) $row['totale'], 2, ',', '.') ?> / mese
          </span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
