<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/dal/db.php';

$title = 'Aggiornamento password';
$jsonFile = __DIR__ . '/../utenti.json';
$jsonData = file_get_contents($jsonFile);

$messages = [];
$errors = [];

if ($jsonData === false) {
    $errors[] = 'Errore: impossibile leggere il file JSON.';
} else {
    $users = json_decode($jsonData, true);
    if (!is_array($users)) {
        $errors[] = 'Errore: formato JSON non valido.';
    }
}

if (empty($errors)) {
    $sql = "INSERT INTO utenti (nome, email, password, ruolo)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE password = ?";
    $stmt = $pdo->prepare($sql);

    foreach ($users as $user) {
        $nome = (string) ($user['nome'] ?? '');
        $email = (string) ($user['email'] ?? '');
        $ruolo = (string) ($user['ruolo'] ?? 'user');
        $password = (string) ($user['password'] ?? '');
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $ok = $stmt->execute([
            $nome,
            $email,
            $hashed,
            $ruolo,
            $hashed,
        ]);

        if ($ok) {
            $messages[] = 'Password hashata e salvata per: ' . htmlspecialchars($nome);
        } else {
            $errors[] = 'Errore per: ' . htmlspecialchars($nome);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="css/update_password.css')) ?>">
</head>
<body class="update-body">
  <main class="update-page">
    <section class="update-card">
      <h1 class="update-title">Aggiornamento password</h1>

      <?php if (!empty($errors)): ?>
        <div class="update-block update-block--error">
          <?php foreach ($errors as $error): ?>
            <p><?= $error ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($messages)): ?>
        <div class="update-block">
          <?php foreach ($messages as $message): ?>
            <p><?= $message ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (empty($errors)): ?>
        <p class="update-success">Tutte le password sono state hashate e salvate con successo!</p>
      <?php endif; ?>

      <p class="update-warning">IMPORTANTE: elimina questo file (update_password.php) per sicurezza!</p>
    </section>
  </main>
</body>
</html>
