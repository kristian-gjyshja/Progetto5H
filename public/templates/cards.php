<div class="stats-grid">

  <div class="stat-card stat-card--indigo">
    <div>
      <p class="stat-label">Abbonamenti Attivi</p>
      <p class="stat-value"><?= (int) $tot ?></p>
    </div>
    <i class="fa-solid fa-file-invoice stat-icon" aria-hidden="true"></i>
  </div>

  <div class="stat-card stat-card--orange">
    <div>
      <p class="stat-label">In Scadenza (7gg)</p>
      <p class="stat-value"><?= (int) $scad ?></p>
    </div>
    <i class="fa-regular fa-clock stat-icon" aria-hidden="true"></i>
  </div>

  <div class="stat-card stat-card--green">
    <div>
      <p class="stat-label">Spesa Mensile</p>
      <p class="stat-value">
        &euro;<?= number_format($spesa_mensile, 2, ',', '.') ?>
      </p>
    </div>
    <i class="fa-solid fa-calendar-days stat-icon" aria-hidden="true"></i>
  </div>

  <div class="stat-card stat-card--purple">
    <div>
      <p class="stat-label">Spesa Annuale</p>
      <p class="stat-value">
        &euro;<?= number_format($spesa_annuale, 2, ',', '.') ?>
      </p>
    </div>
    <i class="fa-solid fa-money-check-dollar stat-icon" aria-hidden="true"></i>
  </div>

</div>
