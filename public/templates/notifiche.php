<div class="panel">
  <h2 class="panel-title">
    <i class="fa-regular fa-bell text-amber-500" aria-hidden="true"></i>
    Notifiche Scadenze Imminenti
  </h2>

  <div class="panel-list">
    <?php if (empty($notifiche)): ?>
      <p class="panel-muted">Nessuna scadenza imminente nei prossimi 7 giorni.</p>
    <?php else: ?>
      <?php foreach (array_slice($notifiche, 0, 5) as $notifica): ?>
        <div class="notice-item">
          <div>
            <p class="notice-title"><?= htmlspecialchars($notifica['nome']) ?></p>
            <p class="notice-meta">Scade il <?= htmlspecialchars($notifica['data_fine']) ?></p>
          </div>
          <span class="notice-badge">Urgente</span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
