<?php
if (!isset($messaggioASchermo) || $messaggioASchermo === null) {
    return;
}

$tipoMessaggioASchermo = $tipoMessaggioASchermo ?? 'success';
$classiMessaggio = $tipoMessaggioASchermo === 'success'
    ? 'bg-emerald-600 border-emerald-700'
    : 'bg-red-600 border-red-700';
?>
<div id="screenMessage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4">
  <div class="w-full max-w-lg rounded-xl border p-5 text-center text-white shadow-2xl <?= $classiMessaggio ?>">
    <p class="text-base font-semibold"><?= htmlspecialchars($messaggioASchermo) ?></p>
    <p class="mt-2 text-xs opacity-90">Clicca per chiudere</p>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const screenMessage = document.getElementById('screenMessage');
    if (!screenMessage) {
      return;
    }

    const chiudiMessaggio = function () {
      screenMessage.classList.add('transition-opacity', 'duration-200', 'opacity-0');
      setTimeout(function () {
        screenMessage.remove();
      }, 220);
    };

    screenMessage.addEventListener('click', chiudiMessaggio);
    setTimeout(chiudiMessaggio, 2600);
  });
</script>
