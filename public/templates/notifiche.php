 <div class="bg-white rounded-xl shadow p-6">
  <h2 class="font-semibold mb-4 flex items-center gap-2">
    <i class="fa-regular fa-bell text-amber-500" aria-hidden="true"></i>
    Notifiche Scadenze Imminenti
  </h2>

  <div class="space-y-3">
    <?php if (empty($notifiche)): ?>
      <p class="text-slate-500">Nessuna scadenza imminente nei prossimi 7 giorni.</p>
    <?php else: ?>
      <?php foreach (array_slice($notifiche, 0, 5) as $notifica): ?>
        <div class="flex justify-between items-center bg-yellow-50 border border-yellow-200 rounded p-4">
          <div>
            <p class="font-medium"><?= htmlspecialchars($notifica['nome']) ?></p>
            <p class="text-sm text-slate-500">Scade il <?= htmlspecialchars($notifica['data_fine']) ?></p>
          </div>
          <span class="bg-orange-500 text-white text-sm px-3 py-1 rounded-full">
            Urgente
          </span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
