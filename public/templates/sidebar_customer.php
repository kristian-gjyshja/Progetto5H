<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$menuItems = [
    ['page' => 'index.php', 'label' => 'Dashboard', 'href' => url('public/customers/index.php'), 'icon' => 'fa-solid fa-gauge-high'],
    ['page' => 'abbonamenti.php', 'label' => 'Abbonamenti', 'href' => url('public/customers/abbonamenti.php'), 'icon' => 'fa-solid fa-file-invoice-dollar'],
];
?>

<aside class="w-64 bg-white shadow-md">
  <nav class="px-4 pt-6 space-y-2">
    <?php foreach ($menuItems as $item): ?>
      <?php
      $isActive = $currentPage === $item['page'];
      $classes = $isActive
          ? 'flex items-center gap-3 px-4 py-2 rounded bg-indigo-600 text-white'
          : 'flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100';
      ?>
      <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $classes ?>">
        <i class="<?= htmlspecialchars($item['icon']) ?> w-5 text-center" aria-hidden="true"></i>
        <span><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
