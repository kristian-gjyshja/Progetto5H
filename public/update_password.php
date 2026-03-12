<?php
require_once __DIR__ . '/../app/dal/db.php';

$jsonFile = __DIR__ . '/../utenti.json';
$jsonData = file_get_contents($jsonFile);
if ($jsonData === false) {
    die('Errore: impossibile leggere il file JSON');
}

$users = json_decode($jsonData, true);
if (!is_array($users)) {
    die('Errore: formato JSON non valido');
}

echo '<h2>Aggiornamento password in corso...</h2>';

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
        echo 'Password hashata e salvata per: <strong>' . htmlspecialchars($nome) . '</strong><br>';
        continue;
    }

    echo 'Errore per: <strong>' . htmlspecialchars($nome) . '</strong><br>';
}

echo '<br><strong>Tutte le password sono state hashate e salvate con successo!</strong><br>';
echo "<strong style='color:red;'>IMPORTANTE: elimina questo file (update_password.php) per sicurezza!</strong>";
?>
