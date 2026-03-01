<?php
require_once __DIR__ . '/../app/config.php';
include '../app/dal/db.php';

// Leggi il file JSON
$json_file = '../utenti.json';
$json_data = file_get_contents(filename: $json_file);
$users = json_decode($json_data, true);

if (!$users) {
    die("Errore: impossibile leggere il file JSON");
}

echo "<h2>Aggiornamento password in corso...</h2>";

foreach ($users as $user) {
    // Hash della password
    $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
    
    // Inserisci o aggiorna nel database
    $sql = "INSERT INTO utenti (nome, email, password, ruolo) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE password = ?";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([
        $user['nome'], 
        $user['email'], 
        $hashed, 
        $user['ruolo'],
        $hashed
    ])) {
        echo "Password hashata e salvata per: <strong>" . htmlspecialchars($user['nome']) . "</strong><br>";
    } else {
        echo "Errore per: <strong>" . htmlspecialchars($user['nome']) . "</strong><br>";
    }
}

echo "<br><strong>Tutte le password sono state hashate e salvate con successo!</strong><br>";
echo "<strong style='color:red;'>IMPORTANTE: Cancella questo file (update_passwords.php) per sicurezza!</strong>";
?>