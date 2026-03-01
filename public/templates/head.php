<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'SubManager Pro' ?></title>

  <script src="https://cdn.tailwindcss.com"></script>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .admin-table th,
    .admin-table td {
      padding: 0.75rem 1rem;
    }
    .admin-table th.sortable-th {
      cursor: pointer;
      user-select: none;
      white-space: nowrap;
    }
    .admin-table th.sortable-th:hover {
      background: #e2e8f0;
    }
    .admin-table th.sortable-th::after {
      content: " \2195";
      color: #94a3b8;
      font-size: 0.75rem;
    }
    .admin-table th.sortable-th[data-sort-direction="asc"]::after {
      content: " \2191";
      color: #334155;
    }
    .admin-table th.sortable-th[data-sort-direction="desc"]::after {
      content: " \2193";
      color: #334155;
    }
    .badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 96px;
      padding: 0.3rem 0.7rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      line-height: 1;
      color: #fff;
      font-weight: 600;
    }
    .badge.attivo { background: #16a34a; }
    .badge.scadenza { background: #f59e0b; }
    .badge.disattivo,
    .badge.scaduto { background: #dc2626; }
    .actions i {
      margin: 0 0.35rem;
      cursor: pointer;
      font-size: 1.2rem;
      color: #334155;
      transition: transform 0.15s ease, color 0.15s ease;
    }
    .actions i:hover {
      color: #1d4ed8;
      transform: scale(1.08);
    }
  </style>
</head>
<body class="bg-slate-100">
